<?php
/**
 * PHP version 5
 *
 * A lazy-loading collection.
 *
 * Loads only the necessary pages out of all the matching records in UserVoice API.
 *
 */
namespace UserVoice;
const PER_PAGE = 100;

class ReadOnlyException extends \Exception {
    function __construct() {
        parent::__construct('Read-only Collection');
    }
}

class Collection implements \Iterator, \ArrayAccess, \Countable {
    private $client;
    private $path;
    private $limit;
    private $per_page;
    private $pages;
    private $response_data;
    private $position;

    function __construct($client, $path, $opts=array()) {
        $this->client = $client;
        $this->path = $path;
        $this->limit = PHP_INT_MAX;
        if (isset($opts['limit'])) {
            $this->limit = $opts['limit'];
        }
        $this->per_page = min($this->limit, PER_PAGE);
        $this->pages = array();
        $this->response_data = null;
    }
    function count() {
        if ($this->response_data == null) {
            $this[0];
        }
        return min($this->response_data['total_records'], $this->limit);
    }
    public function offsetExists($offset) {
        return $offset >= 0 && $offset < count($this);
    }
    public function offsetGet($offset) {
        $value = NULL;
        if ($offset >= 0 && $offset < $this->limit) {
            $page = $this->loadPage(floor($offset / PER_PAGE) + 1);
            $offsetInPage = $offset % PER_PAGE;
            if (isset($page[$offsetInPage])) {
                $value = $page[$offsetInPage];
            }
        }
        return $value;
    }
    private function loadPage($i) {
        if (!isset($this->pages[$i])) {
            $url = $this->path;
            if (strpos($this->path, '?') === false) {
                $url .= '?';
            } else {
                $url .= '&';
            }
            $result = $this->client->get($url . "per_page=$this->per_page&page=$i");
            if (isset($result['response_data'])) {
                $this->response_data = $result['response_data'];
                unset($result['response_data']);
                $page = array_pop($result);
                if ($page !== NULL) {
                    $this->pages[$i] = $page;
                }
            }
        }
        if (!isset($this->pages[$i])) {
            throw NotFound('The resource you requested is not a collection');
        }
        return $this->pages[$i];
    }
    public function offsetUnset($offset) { throw ReadOnlyException(); }
    public function offsetSet($offset, $value) { throw ReadOnlyException(); }

    public function current() {
        return $this[$this->position];
    }
    public function key() {
        return $this->position;
    }
    public function next() {
        ++$this->position;
    }
    public function rewind() {
        $this->position = 0;
    }
    public function valid() {
        return $this->offsetExists($this->position);
    }
}
?>
