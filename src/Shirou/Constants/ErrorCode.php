<?php
namespace Shirou\Constants;

class ErrorCode
{
    // General
    const GENERAL_NOTFOUND = 1001;
    const GENERAL_SERVER_ERROR = 1002;

    // Data
    const DATA_DUPLICATE = 2001,
        DATA_NOTFOUND = 2002,
        DATA_INVALID = 2004,
        DATA_FAIL = 2005,
        DATA_FIND_FAIL = 2010,
        DATA_CREATE_FAIL = 2020,
        DATA_UPDATE_FAIL = 2030,
        DATA_DELETE_FAIL = 2040,
        DATA_REJECTED = 2060,
        DATA_NOTALLOWED = 2070,
        DATA_BULK_FAILED = 2080;

    // File upload
    const FILE_UPLOAD_ERR_MIN_SIZE = 2500,
        FILE_UPLOAD_ERR_MAX_SIZE = 2501,
        FILE_UPLOAD_ERR_ALLOWED_FORMAT = 2502,
        FILE_UPLOAD_ERR = 2503,
        FILE_DELETE_ERR = 2504;

    // PDO
    const PDO_DUPLICATE_ENTRY = 2300;

    // QUEUE
    const QUEUE_PUT_FAIL = 2400;
}
