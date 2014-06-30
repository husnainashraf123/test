<?php

namespace Clients\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Debug\Debug;


class LeadTable {

    protected $tableGateway;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
    }

    public function saveLead(Lead $lead) {
//                              Debug::dump($lead);exit;
        $data = array(
            'name' => $lead->name,
        );
        $id = (int) $lead->id;
        if ($id == 0) {
            $this->tableGateway->insert($data);
            
            $id = $this->tableGateway->lastInsertValue;
//            $id->buffer();
//            $id->next()();
            return $id;
        } else {
            if ($this->getLead($id)) {
//                                                                                       echo '<pre>';
//                        print_r($data);
//                        echo '</pre>';
//                        exit;
                $this->tableGateway->update($data, array('id' => $id));
            } else {
                throw new \Exception('Lead ID does not exist');
            }
        }
    }
    public function fetchAll() {
        $resultSet = $this->tableGateway->select();
         $resultSet->buffer();
        return $resultSet;
    }

    public function getLead($id) {
        $id = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }

    public function getLeadByEmail($leadEmail) {
        $rowset = $this->tableGateway->select(array('email' =>
            $leadEmail));
        $row = $rowset->current();
        if (!$row) {
            throw new \Exception("Could not find row $leadEmail");
        }
        return $row;
    }

    public function deleteLead($id) {
        $this->tableGateway->delete(array('id' => $id));
    }

}
