<?php
namespace UserVoice;

class APIError extends \Exception {
    function __construct($details) {
        parent::__construct(serialize($details));
    }
}
class ApplicationError extends APIError { }
class NotFound extends APIError { }
class Unauthorized extends APIError { }
?>
