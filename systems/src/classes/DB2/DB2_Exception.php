<?php

class DB2_Exception extends PDOException
{
    private $sqlstate_messages = array(
        '00000' => "Successful completion",
        '01000' => "Warning",
        '01001' => "Cursor operation conflict",
        '01002' => "Disconnect error",
        '01003' => "Null value eliminated in set function",
        '01004' => "String data, right truncation",
        '01005' => "Insufficient item descriptor areas",
        '01006' => "Privilege not revoked",
        '01007' => "Privilege not granted",
        '01008' => "Implicit zero-bit padding",
        '01009' => "Search expression too long for information schema ",
        '0100A' => "Query expression too long for information schema",
        '01S00' => "Invalid connection string attribute",
        '01S01' => "Error in row",
        '01S02' => "Option value changed",
        '02000' => "No data",
        '07000' => "Dynamic SQL error",
        '07001' => "Using clause doesn't match dynamic parameters",
        '07002' => "Using clause doesn't match target specifications",
        '07003' => "Cursor specification can't be executed",
        '07004' => "Using clause required for dynamic parameters",
        '07005' => "Prepared statement not a cursor specification",
        '07006' => "Restricted data type attribute violation",
        '07007' => "Using clause required for result fields",
        '07008' => "Invalid descriptor count",
        '07009' => "Invalid descriptor index",
        '08000' => "Connection Exception",
        '08001' => "SQL-client unable to establish SQL-connection",
        '08002' => "Connection name in use",
        '08003' => "Connection doesn't exist",
        '08004' => "SQL-server rejected establishment of SQL-connection",
        '08006' => "Connection failure",
        '08007' => "Transaction resolution unknown",
        '08900' => "Server lookup failed",
        '08P01' => "Protocol violation",
        '08S01' => "Communication link failure",
        '0A000' => "Feature not supported",
        '0A001' => "Multiple server transactions",
        '21000' => "Cardinality violation",
        '22000' => "Data exception",
        '22001' => "String data, right truncation",
        '22002' => "Null value, no indicator",
        '22003' => "Numeric value out of range",
        '22005' => "Error in assignment",
        '22007' => "Invalid date-time format",
        '22008' => "Date-time field overflow",
        '22009' => "Invalid time zone displacement value",
        '22011' => "Substring error",
        '22012' => "Division by zero",
        '22015' => "Internal field overflow",
        '22018' => "Invalid character value for cast",
        '22019' => "Invalid escape character",
        '22021' => "Character not in repertoire",
        '22022' => "Indicator overflow",
        '22023' => "Invalid parameter value",
        '22024' => "Unterminated C string",
        '22025' => "Invalid escape sequence",
        '22026' => "String data, length mismatch",
        '22027' => "Trim error",
        '2200F' => "Zero Length Character String",
        '22P01' => "Floating Point Exception",
        '22P02' => "Invalid Text Representation",
        '22P03' => "Invalid Binary Representation",
        '22P04' => "Bad Copy File Format",
        '22P05' => "Untranslatable Character",
        '2200L' => "Not an XML Document",
        '2200M' => "Invalid XML Document",
        '2200N' => "Invalid XML Content",
        '2200S' => "Invalid XML Comment",
        '2200T' => "Invalid XML Processing Instruction",
        '23000' => "Integrity constraint violation",
        '23001' => "Restrict Violation",
        '23502' => "Not NULL Violation",
        '23503' => "Foreign Key Violation",
        '23505' => "Unique Violation",
        '23514' => "Check Violation",
        '24000' => "Invalid cursor state",
        '25000' => "Invalid transaction state",
        '26000' => "Invalid SQL statement name",
        '27000' => "Triggered data change violation",
        '28000' => "Invalid authorization specification",
        '2A000' => "Syntax error or access rule violation in direct SQL statement",
        '2B000' => "Dependent privilege descriptors still exist",
        '2C000' => "Invalid character set name",
        '2D000' => "Invalid transaction termination",
        '2E000' => "Invalid connection name",
        '33000' => "Invalid SQL descriptor name",
        '34000' => "Invalid cursor name",
        '35000' => "Invalid condition number",
        '37000' => "Syntax error or access rule violation in dynamic SQL statement",
        '3C000' => "Ambiguous cursor name",
        '3F000' => "Invalid schema name",
        '40000' => "Transaction rollback",
        '40001' => "Serialization failure",
        '40002' => "Integrity constraint violation",
        '40003' => "Statement completion unknown",
        '42000' => "Syntax error or access rule violation",
        '42501' => "Insufficient Privilege",
        '42601' => "Syntax Error",
        '42602' => "Invalid Name",
        '42611' => "Invalid Column Definition",
        '42622' => "Name Too Long",
        '42701' => "Duplicate Column",
        '42702' => "Ambiguous Column",
        '42703' => "Undefined Column",
        '42704' => "Undefined Object",
        '42710' => "Duplicate Object",
        '42712' => "Duplicate Alias",
        '42723' => "Duplicate Function",
        '42725' => "Ambiguous Function",
        '42803' => "Grouping Error",
        '42804' => "Datatype Mismatch",
        '42809' => "Wrong Object Type",
        '42830' => "Invalid Foreign Key",
        '42846' => "Cannot Coerce",
        '42883' => "Undefined Function",
        '42939' => "Reserved Name",
        '42P01' => "Undefined Table",
        '42P02' => "Undefined Parameter",
        '42P03' => "Duplicate Cursor",
        '42P04' => "Duplicate Database",
        '42P05' => "Duplicate Prepared Statement",
        '42P06' => "Duplicate Schema",
        '42P07' => "Duplicate Table",
        '42P08' => "Ambiguous Parameter",
        '42P09' => "Ambiguous Alias",
        '42P10' => "Invalid Column Reference",
        '42P11' => "Invalid Cursor Definition",
        '42P12' => "Invalid Database Definition",
        '42P13' => "Invalid Function Definition",
        '42P14' => "Invalid Prepared Statement Definition",
        '42P15' => "Invalid Schema Definition",
        '42P16' => "Invalid Table Definition",
        '42P17' => "Invalid Object Definition",
        '42P18' => "Indeterminate Datatype",
        '42P19' => "Invalid Recursion",
        '42P20' => "Windowing Error",
        //'42P01' => "Relation does not exist",
        //'42S01' => "Base table or view already exists",
        //'42S02' => "Base table or view not found",
        '44000' => "With check option violation",


        'HY000' => "General error.",
        'HY001' => "Memory allocation failure.",
        'HY002' => "Invalid column number.",
        'HY003' => "Program type out of range.",
        'HY004' => "Invalid SQL data type.",
        'HY009' => "Invalid use of a null pointer.",
        'HY010' => "Function sequence error.",
        'HY011' => "Operation invalid at this time.",
        'HY012' => "Invalid transaction code.",
        'HY013' => "Unexpected memory handling error.",
        'HY014' => "No more handles.",
        'HY015' => "No cursor name available.",
        'HY019' => "Numeric value out of range.",
        'HY024' => "Invalid argument value.",
        'HY090' => "Invalid string or buffer length.",
        'HY091' => "Descriptor type out of range.",
        'HY092' => "Option type out of range.",
        'HY093' => "Invalid parameter number.",
        'HY096' => "Information type out of range.",
        'HY097' => "Column type out of range.",
        'HY098' => "Scope type out of range.",
        'HY099' => "Nullable type out of range.",
        'HY100' => "Uniqueness option type out of range.",
        'HY101' => "Accuracy option type out of range.",
        'HY103' => "Direction option out of range.",
        'HY104' => "Invalid precision value.",
        'HY105' => "Invalid parameter type.",
        'HY106' => "Fetch type out of range.",
        'HY107' => "Row value out of range.",
        'HY109' => "Invalid cursor position.",
        'HY110' => "Invalid driver completion.",
        'HY501' => "Invalid data source name.",
        'HY506' => "Error closing a file.",
        'HYC00' => "Driver not capable.",

    );

    private $general_error_messages = array(
    );

    private $error_messages = array(
        '102'  => "Incorrect syntax.",
        '156'  => "Incorrect syntax.",
        '207'  => "Invalid column name.",
        '208'  => "Invalid object/table name.",
        '220'  => "Arithmetic overflow error",
        '241'  => "Syntax error converting datetime from character string.",
        '242'  => "The conversion of a char data type to a datetime data type resulted in an out-of-range datetime value.",
        '244'  => "Integer overflow. Use a larger integer column.",
        '245'  => "Syntax error converting the varchar value to a column of data type int.",
        '248'  => "Maximum integer value exceeded.",
        '257'  => "Implicit conversion is not allowed.",
        '515'  => "Cannot insert NULLs.",
        '547'  => "Foreign Key/Check constraint.",
        '1011' => "Field name selected multiple times.",
        '1265' => "Data truncated",
        '1364' => "Field doesn't have a default value",
        '2601' => "Cannot insert duplicate key row.",
        '2627' => "Cannot insert duplicate key.",
        '8114' => "Error converting data type",
        '8115' => "Arithmetic overflow error converting numeric to data type numeric/varchar.",
        '8120' => "Invalid column in select list because it is not contained in either an aggregate function or the GROUP BY clause.",
        '8152' => "String or binary data would be truncated."
    );

    public function __construct(PDOStatement $stmt, $dbname='')
    {
        $error_code = $stmt->errorCode();
        $error_info = $stmt->errorInfo();
        //error_log("PDOStatement Error code: " . print_r($error_code, true));
        //error_log("PDOStatement Error info: " . print_r($error_info, true));
        //var_dump($error_info[0] . ": " . $error_info[2]);
        //var_dump($stmt);
        //$stmt->debugDumpParams();

        if ($error_info[0] == 'HY000') {
            //var_dump($error_info[0] . ": " . $error_info[2]);
            $truncate_pos = strpos($error_info[2], ':');
            if ($truncate_pos !== false) {
                $str = substr($error_info[2], 0, $truncate_pos);
                if (array_key_exists($error_info[1], $this->error_messages)) {
                    $str .= ": " . $this->error_messages[$error_info[1]];
                }
            } else {
                if (array_key_exists($error_info[1], $this->error_messages)) {
                    $str = $this->error_messages[$error_info[1]];
                } else {
                    $str = $error_info[2];
                }
            }
        } elseif ($error_info[0] == '01000') {
            if (array_key_exists($error_info[1], $this->error_messages)) {
                $str = $this->sqlstate_messages[$error_info[0]] . ": " . $this->error_messages[$error_info[1]];
            } else {
                $str = $this->sqlstate_messages[$error_info[0]] . ": " . $error_info[2];
            }
        } elseif (array_key_exists($error_info[0], $this->sqlstate_messages)) {
            $str = $this->sqlstate_messages[$error_info[0]];
        } elseif (array_key_exists(substr($error_info[0], 0, 2) . '000', $this->sqlstate_messages)) {
            $str = $this->sqlstate_messages[substr($error_info[0], 0, 2) . '000'];
        } else {
            print_r("Unknown error detected: " . $error_info[0] . ": " . $error_info[2]);
            $str = $error_info[2];
        }

        $this->code = $error_code;
        if (!empty($dbname)) $str = "[$dbname] $str";
        $this->message = $str;

        $stmt = null;
        //var_dump("$this->code: $this->message");
    }

}
