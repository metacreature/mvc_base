
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

	static function mysql_to_php($date_string, $format) 
	{
		if (!is_string($date_string)) {
            return null;
        }
		
        $date_object = DateTime::createFromFormat(self::$mysql_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

		return $date_object->format(self::$php_formats[$format]);
	}

	static function php_to_mysql($date_string, $format) 
	{
		if (!is_string($date_string)) {
            return null;
        }
		
        $date_object = DateTime::createFromFormat(self::$php_formats[$format], $date_string);
        if ($date_object === false) {
            return null;
        }

		return $date_object->format(self::$mysql_formats[$format]);
	}

	static function obj_to_mysql($date_object, $format)
    {
        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$mysql_formats[$format]);
        }
        return null;
    }

	static function obj_to_php($date_object, $format)
    {
        if ($date_object instanceof DateTime) {
            return $date_object->format(self::$php_formats[$format]);
        }
        return null;
    }

    static function mysql_to_obj($date_string, $format)
    {
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