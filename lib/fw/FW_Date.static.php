<?php 



class FW_Date{

    const FORMAT_DATETIME = 'dt';
    const FORMAT_DATE = 'd';
    const FORMAT_TIME = 't';

	protected static $_mysql_formats = array(
        'dt' => 'Y-m-d H:i:s',
        'd' => 'Y-m-d',
        't' => 'H:i:s'
    );

    protected static $_user_formats = null;

    protected static $_mapping = array(
        'd'=>'DD',
        'j'=>'D',
        'm'=>'MM',
        'n'=>'M',
        'M'=>'Mon',
        'F'=>'Month',
        'Y'=>'YYYY',
        'y'=>'YY',
        'g'=>'H',
        'G'=>'H',
        'h'=>'H',
        'H'=>'HH',
        'i'=>'MM',
        's'=>'SS',
        'a'=>'am/pm',
        'A'=>'AM/PM'
    );

    protected static function _test_init() {
        if (!is_array( self::$_user_formats)) {
            throw new Exception('FW_Date: PHP  date-formats not set!');
        }
    }

	static function set_user_formats($date_time, $date, $time) 
    {
        self::$_user_formats = array(
            'dt' => $date_time,
            'd' => $date,
            't' => $time
        );
    }

    static function get_user_format($format) 
    {
        return self::$_user_formats[$format];
    }

    static function get_display_format($format) 
    {
        return strtr(self::$_user_formats[$format], self::$_mapping);
    }

	static function mysql_to_user($date_string, $format, $format_to = null) 
	{
        self::_test_init();

        if (is_null($format_to)) {
            $format_to = $format;
        }
		if (!is_string($date_string)) {
            return null;
        }
		
        $date_object = self::mysql_to_obj($date_string, $format);
        if ($date_object) {
		    return $date_object->format(self::$_user_formats[$format_to]);
        }
	}

	static function user_to_mysql($date_string, $format, $format_to = null) 
	{
        self::_test_init();

        if (is_null($format_to)) {
            $format_to = $format;
        }
		if (!is_string($date_string)) {
            return null;
        }
		
        $date_object = self::user_to_obj($date_string, $format);
        if ($date_object) {
            return $date_object->format(self::$_mysql_formats[$format_to]);
        }
	}

	static function obj_to_mysql($date_object, $format)
    {
        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$_mysql_formats[$format]);
        }
        return null;
    }

	static function obj_to_user($date_object, $format)
    {
        self::_test_init();

        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$_user_formats[$format]);
        }
        return null;
    }

    static function mysql_to_obj($date_string, $format)
    {
        if (!is_string($date_string)) {
            return null;
        }

        $date_object = DateTime::createFromFormat(self::$_mysql_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

        switch ($format) {
            case self::FORMAT_DATE:
                $date_object->setTime(0, 0, 0);
                break;
            case self::FORMAT_TIME:
                $date_object->setDate(1900, 1, 1);
            default:
                if (strpos(self::$_user_formats[$format], 's') === false) {
                    $date_object->setTime($date_object->format('h'), $date_object->format('i'), 0);
                }
                break;
        }
        return $date_object;
    }

    static function user_to_obj($date_string, $format)
    {
        self::_test_init();

        if (!is_string($date_string)) {
            return null;
        }
        
        $date_object = DateTime::createFromFormat(self::$_user_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

        switch ($format) {
            case self::FORMAT_DATE:
                $date_object->setTime(0, 0, 0);
                break;
            case self::FORMAT_TIME:
                $date_object->setDate(1900, 1, 1);
            default:
                if (strpos(self::$_user_formats[$format], 's') === false) {
                    $date_object->setTime($date_object->format('h'), $date_object->format('i'), 0);
                }
                break;
        }
        return $date_object;
    }

}