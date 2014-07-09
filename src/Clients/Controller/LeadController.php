<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @lead      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Clients\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Debug\Debug;
use Zend\Session\Container;
use Clients\Model\Lead;
use Clients\Model\LeadTable;
use Clients\Model\Website;
use Clients\Model\WebsiteTable;
use Clients\Form\AddLeadForm;
use Clients\Form\AddLeadFilter;
use Clients\Form\EditLeadForm;
use Clients\Form\EditLeadFilter;

class LeadController extends AbstractActionController {

    public function leaddataAction() {
        if ($_POST) {
            $data = $_POST;
            $tableGatewayWebsite = $this->getConnectionWebsite();
            $websiteTable = new WebsiteTable($tableGatewayWebsite);
            $website_data = $websiteTable->getWebsiteByName($data['website']);
            if ($website_data) {
                $website_id = $website_data->id;
            } else {
                print_r("Cannot find any Website");
                exit;
            }
            $tableGateway = $this->getConnection();
            $leadTable = new LeadTable($tableGateway);

            $lead = new Lead();
            $lead->comments = $data['comments'];
            $lead->website_id = $website_id;
            $lead->caller_type = $data['caller_type'];
            $date = explode('/', $data['lead_date']);
            $lead->lead_date = $date[2] . '-' . $date[0] . '-' . $date[1];
            $lead->lead_source = $data['lead_source'];
            $lead->client_name = $data['client_name'];
            $lead->website = $data['website'];
            $lead->inc_phone = $data['inc_phone'];
            $lead->call_time = $data['call_time'];
            $lead->call_duration = $data['call_duration'];
            $lead->lead_name = $data['lead_name'];
            $lead->lead_email = $data['lead_email'];
            $id = $leadTable->saveLead($lead);
            return 0;
        }
    }

    public function indexAction() {

        $id = (int) $this->params()->fromRoute('id', 0);

        $session = new Container('lead');
        $session->offsetSet('lead_client_id', $id);


        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        $tableGatewayWebsite = $this->getConnectionWebsite();
        $websiteTable = new WebsiteTable($tableGatewayWebsite);

        $tableGateway = $this->getConnection();
        $leadTable = new LeadTable($tableGateway);

        if ($session->offsetExists('current_website_id') && $session->offsetGet('current_website_id') != '') {
            $current_website_id = $session->offsetGet('current_website_id');
            if ($session->offsetExists('from') && $session->offsetGet('from') != '') {
                $current_website_lead = $this->setDateRange();
//                print_r($current_website_lead);exit;
            } else {
                $current_website_lead = $leadTable->getLeadWebsite($current_website_id);
            }
            if (!empty($current_website_lead)) {

                $viewModel = new ViewModel(array(
                    'client_websites' => $websiteTable->getWebsiteClients($id),
                    'message' => $session->offsetGet('msg'),
                    'website_data' => $current_website_lead,
                    'current_website_id' => $current_website_id
                ));
            } else {
                $viewModel = new ViewModel(array(
                    'client_websites' => $websiteTable->getWebsiteClients($id),
                    'message' => $session->offsetGet('msg'),
                    'website_data' => $current_website_lead,
                    'current_website_id' => $current_website_id
                ));
            }
        } else {

            $client_websites = $websiteTable->getWebsiteClients($id);

            foreach ($client_websites as $value) {
                $current_website_id = $value->id;
                $current_website_lead = $leadTable->getLeadWebsite($value->id);
                break;
            }
            $viewModel = new ViewModel(array(
                'client_websites' => $client_websites,
                'website_data' => $current_website_lead,
                'current_website_id' => $current_website_id
            ));
        }

        return $viewModel;
    }

    public function addAction() {
        $id = (int) $this->params()->fromRoute('id', 0);

        $session = new Container('lead');
        $lead_client_id = $session->offsetGet('lead_client_id');
        $session->offsetSet('current_website_id', $id);

        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
//                        'controller' => 'lead',
                        'action' => 'index',
                        'id' => $lead_client_id
            ));
        }

        $form = new AddLeadForm();

        if ($this->request->isPost()) {
            $tableGateway = $this->getConnection();
            $post = $this->request->getPost();

            $post->website_id = $id;

            $originalDate = $post->lead_date;
            $newDate = date("Y-m-d", strtotime($originalDate));
            $post->lead_date = $newDate;
//              print_r($post);exit;
            $lead = new Lead();
            $lead->exchangeArray($post);
            $leadTable = new LeadTable($tableGateway);

            $id = $leadTable->saveLead($lead);
            $session->offsetSet('msg', "Lead has been successfully Added.");
            return $this->redirect()->toUrl('/lead/index/' . $lead_client_id);
        }

        //print_r('here');exit;
        $viewModel = new ViewModel(array('form' => $form, 'id' => $id));
        return $viewModel;
    }

    public function changewebsiteAction() {
        $website_id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('lead');
        $lead_client_id = $session->offsetGet('lead_client_id');
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('msg', "");
        return $this->redirect()->toUrl('/lead/index/' . $lead_client_id);
//         print_r($website_id);exit;
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('lead');
        $lead_client_id = $session->offsetGet('lead_client_id');
//        $session->offsetSet('current_website_id', $id);
        $session->offsetSet('msg', "Lead has been successfully Updated.");
        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'add'
            ));
        }
        $tableGateway = $this->getConnection();
        $leadTable = new LeadTable($tableGateway);


        $form = new EditLeadForm();
        if ($this->request->isPost()) {

            $post = $this->request->getPost();
            //saving Client data table
            $lead = $leadTable->getLead($post->id);

            $form->bind($lead);
            $form->setData($post);
//            print_r($post);exit;
            $originalDate = $post->lead_date;
            $newDate = date("Y-m-d", strtotime($originalDate));
            $post->lead_date = $newDate;
            $lead->caller_type = $post->caller_type;
            $lead->lead_source = $post->lead_source;
            $lead->inc_phone = $post->inc_phone;
            $lead->call_duration = $post->call_duration;
            $lead->lead_name = $post->lead_name;
            $lead->lead_email = $post->lead_email;
            $session->offsetSet('current_website_id', $lead->website_id);
//             print_r($lead);exit;
            $leadTable->saveLead($lead);
            return $this->redirect()->toUrl('/lead/index/' . $lead_client_id);
        }
        $lead = $leadTable->getLead($this->params()->fromRoute('id'));
        $originalDate = $lead->date;
        $newDate = date("m/d/Y", strtotime($originalDate));
        $lead->date = $newDate;
        $form->bind($lead); //biding data to form
        $viewModel = new ViewModel(array(
            'form' => $form,
            'id' => $this->params()->fromRoute('id'),
        ));
        return $viewModel;
    }

    public function deleteAction() {
        header('Content-Type: application/json');

        $id = (int) $this->params()->fromRoute('id', 0);
//                    Debug::dump($id);exit;
        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        //delete Lead for a client website
        $tableGateway = $this->getConnection();
        $leadTable = new LeadTable($tableGateway);
        $leadTable->deleteLead($id);


        echo json_encode(array('data' => ''));
        exit();
    }

    public function getLeadByIdAction() {
        header('Content-Type: application/json');
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        $tableGateway = $this->getConnection();
        $leadTable = new LeadTable($tableGateway);
        $data = $leadTable->getLeadWebsite($id);
        echo json_encode(array('data' => (array) $data));
        exit();
    }

    public function setDateRange() {
        $session = new Container('lead');
        $from = $session->offsetGet('from');
        $till = $session->offsetGet('till');
        $website_id = $session->offsetGet('current_website_id');

        $tableGateway = $this->getConnection();
        $leadTable = new LeadTable($tableGateway);
        $website_leads_data = $leadTable->dateRange($from, $till, $website_id);
        return $website_leads_data;
    }

    public function daterangeAction() {      // finding daterange data from database
        $daterange = $_GET['daterange'];
        $website_id = $_GET['websiteid'];

        $ranges = explode('-', $daterange);
        $all_ranges = array();
        foreach ($ranges as $range) {
            $range = trim($range);
            $parts = explode(' ', $range);
            $month = date("m", strtotime($parts[0]));
            $day = rtrim($parts[1], ',');
            $all_ranges[] = $parts[2] . '-' . $month . '-' . $day;
        }
        $session = new Container('lead');
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('from', $all_ranges[0]);
        $session->offsetSet('till', $all_ranges[1]);
        $session->offsetSet('daterange', $daterange);
        $lead_client_id = $session->offsetGet('lead_client_id');
        return $this->redirect()->toUrl('/lead/index/' . $lead_client_id);
    }

    public function setmessageAction() {  // set message for delete client lead
        $session = new Container('lead');
        $lead_client_id = $session->offsetGet('lead_client_id');
        $website_id = (int) $this->params()->fromRoute('id', 0);
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('msg', "Lead has been successfully Deleted.");
//        print_r($website_id);exit;
        return $this->redirect()->toUrl('/lead/index/' . $lead_client_id);
    }

    public function getConnection() {
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $resultSetPrototype = new \Zend\Db\ResultSet\ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new
                \Clients\Model\Lead);
        $tableGateway = new \Zend\Db\TableGateway\TableGateway('leads', $dbAdapter, null, $resultSetPrototype);
        return $tableGateway;
    }

    public function getConnectionWebsite() {        // set connection to Website table
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $resultSetPrototype = new \Zend\Db\ResultSet\ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new
                \Clients\Model\Website);
        $tableGateway = new \Zend\Db\TableGateway\TableGateway('websites', $dbAdapter, null, $resultSetPrototype);
        return $tableGateway;
    }

}
