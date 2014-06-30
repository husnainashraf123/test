<?php

namespace Clients\Model;

class Website {

    public $id;
    public $website;
    public $clients_id;


    function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->website = (isset($data['website'])) ? $data['website'] : null;
        $this->clients_id = (isset($data['clients_id'])) ? $data['clients_id'] : null;

    }
         // Add the following method:
     public function getArrayCopy()
     {
         return get_object_vars($this);
     }


}
