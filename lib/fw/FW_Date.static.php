<?php 



class FW_Date{

    const FORMAT_DATETIME = 'dt';
    const FORMAT_DATE = 'd';
    const FORMAT_TIME = 't';

	protected static $mysql_formats = array(
        'dt' => 'Y-m-d H:i:s',
        'd' => 'Y-m-d',
        't' => 'H:i:s'
    );

    protected static $php_formats = null;

	static function set_formats($date_time, $date, $time) 
    {
        self::$php_formats = array(
            'dt' => $date_time,
            'd' => $date,
            't' => $time
        );
    }

    private static function _test_init() {
        if (!is_array( self::$php_formats)) {
            throw new Exception('FW_Date: PHP  date-formats not set!');
        }
    }

	static function mysql_to_php($date_string, $format, $format_to = null) 
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
		    return $date_object->format(self::$php_formats[$format_to]);
        }
	}

	static function php_to_mysql($date_string, $format, $format_to = null) 
	{
        self::_test_init();

        if (is_null($format_to)) {
            $format_to = $format;
        }
		if (!is_string($date_string)) {
            return null;
        }
		
        $date_object = self::php_to_obj($date_string, $format);
        if ($date_object) {
            return $date_object->format(self::$mysql_formats[$format_to]);
        }
	}

	static function obj_to_mysql($date_object, $format)
    {
        self::_test_init();

        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$mysql_formats[$format]);
        }
        return null;
    }

	static function obj_to_php($date_object, $format)
    {
        self::_test_init();

        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$php_formats[$format]);
        }
        return null;
    }

    static function mysql_to_obj($date_string, $format)
    {
        self::_test_init();

        if (!is_string($date_string)) {
            return null;
        }

        $date_object = DateTime::createFromFormat(self::$mysql_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

        switch ($format) {
            case self::FORMAT_DATE:
                $date_object->setTime(0, 0, 0);
                break;
            case self::FORMAT_TIME:
                $date_object->setDate(1900, 1, 1);
                break;
            default:
                break;
        }
        return $date_object;
    }

    static function php_to_obj($date_string, $format)
    {
        self::_test_init();

        if (!is_string($date_string)) {
            return null;
        }
        
        $date_object = DateTime::createFromFormat(self::$php_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

        switch ($format) {
            case self::FORMAT_DATE:
                $date_object->setTime(0, 0, 0);
                break;
            case self::FORMAT_TIME:
                $date_object->setDate(1900, 1, 1);
                break;
            default:
                break;
        }
        return $date_object;
    }

}