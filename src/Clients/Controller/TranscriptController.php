<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @transcript      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Clients\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Debug\Debug;
use Zend\Filter\Compress;
use Clients\Model\Website;
use Clients\Model\WebsiteTable;
use Clients\Model\Transcript;
use Clients\Model\TranscriptTable;
use Clients\Form\AddTranscriptForm;
use Clients\Form\AddTranscriptFilter;
use Clients\Form\EditTranscriptForm;
use Clients\Form\EditTranscriptFilter;
use Zend\Session\Container;

class TranscriptController extends AbstractActionController {

    public function indexAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('transcript');
        $session->offsetSet('transcript_client_id', $id);


        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        $tableGatewayWebsite = $this->getConnectionWebsite();
        $websiteTable = new WebsiteTable($tableGatewayWebsite);

        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);

        if ($session->offsetExists('current_website_id') && $session->offsetGet('current_website_id') != '') {
            $current_website_id = $session->offsetGet('current_website_id');
            if ($session->offsetExists('from') && $session->offsetGet('from') != '') {
                $current_website_transcript = $this->setDateRange();
//                print_r($current_website_transcript);exit;
            } else {
                $current_website_transcript = $transcriptTable->getTranscriptWebsite($current_website_id);
            }


            if (!empty($current_website_transcript)) {

                $viewModel = new ViewModel(array(
                    'client_websites' => $websiteTable->getWebsiteClients($id),
                    'message' => $session->offsetGet('msg'),
                    'website_data' => $current_website_transcript,
                    'current_website_id' => $current_website_id
                ));
            } else {
                $viewModel = new ViewModel(array(
                    'client_websites' => $websiteTable->getWebsiteClients($id),
                    'message' => $session->offsetGet('msg'),
                    'website_data' => $current_website_transcript,
                    'current_website_id' => $current_website_id
                ));
            }
        } else {

            $client_websites = $websiteTable->getWebsiteClients($id);
//           print_r($client_websites);
            foreach ($client_websites as $value) {
//                  print_r($value);exit;
                $current_website_id = $value->id;

                $current_website_transcript = $transcriptTable->getTranscriptWebsite($value->id);
                break;
            }

            $viewModel = new ViewModel(array(
                'client_websites' => $client_websites,
                'website_data' => $current_website_transcript,
                'current_website_id' => $current_website_id
            ));
        }

        return $viewModel;
    }

    public function addAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('transcript');
        $transcript_client_id = $session->offsetGet('transcript_client_id');
        $session->offsetSet('current_website_id', $id);

        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
//                        'controller' => 'transcript',
                        'action' => 'index',
                        'id' => $transcript_client_id
            ));
        }
        $form = new AddTranscriptForm();
        if ($this->request->isPost()) {

            $post = $this->request->getPost();

            $uploadFile = $this->params()->fromFiles('fileupload');
//            $ufiles = json_encode($this->request->getFiles()->toArray());
            $post = array_merge_recursive(
                    $this->request->getPost()->toArray(),
//                                $this->request->getFiles()->toArray()                    
                    array('fileupload' => $uploadFile['name'])
            );
            $adapter = new \Zend\File\Transfer\Adapter\Http();
            $uploadPath = getcwd() . '\module\Clients\data\uploads\\' . $id;
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $adapter->setDestination($uploadPath);
            if ($adapter->receive($uploadFile['name'])) {

                $post['website_id'] = $id;
                $post['date_posted'] = date("Y-m-d", strtotime($post['date_posted']));
                $post['date_received'] = date("Y-m-d", strtotime($post['date_received']));
                $post['date_revised'] = date("Y-m-d", strtotime($post['date_revised']));

                $transcript = new Transcript();
                $transcript->exchangeArray($post);
//             print_r($transcript);exit;
                $tableGateway = $this->getConnection();
                $transcriptTable = new TranscriptTable($tableGateway);

                $id = $transcriptTable->saveTranscript($transcript);
                $session->offsetSet('msg', "Transcript has been successfully Added.");
                return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
            } else {
                print_r("Could not get file in uploads folder");
                exit();
            }
        }
//        print($form);exit;

        $viewModel = new ViewModel(array('form' => $form, 'id' => $id));
        return $viewModel;
    }

    public function changewebsiteAction() {
        $website_id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('transcript');
        $transcript_client_id = $session->offsetGet('transcript_client_id');
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('msg', "");
        return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
    }

    public function editAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('transcript');
        $transcript_client_id = $session->offsetGet('transcript_client_id');
        $session->offsetSet('msg', "Transcript has been successfully Updated.");
        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'add'
            ));
        }
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);
        $transcript = $transcriptTable->getTranscript($this->params()->fromRoute('id'));
        $file_name = $transcript->fileupload;
        $form = new EditTranscriptForm();
        if ($this->request->isPost()) {
            $uploadFile = $this->params()->fromFiles('fileupload');
            $adapter = new \Zend\File\Transfer\Adapter\Http();
            if ($uploadFile['name'] == '') {
                $post = array_merge_recursive(
                        $this->request->getPost()->toArray(), array('fileupload' => $file_name)
                );
                //saving Client data table
                $transcript = $transcriptTable->getTranscript($post['id']);
                $form->bind($transcript);
                $form->setData($post);

                $post['date_posted'] = date("Y-m-d", strtotime($post['date_posted']));
                $post['date_received'] = date("Y-m-d", strtotime($post['date_received']));
                $post['date_revised'] = date("Y-m-d", strtotime($post['date_revised']));
                $transcript->date_posted = $post['date_posted'];
                $transcript->date_revised = $post['date_revised'];
                $transcript->date_received = $post['date_received'];
                $transcript->name = $post['name'];
                $transcript->fileupload = $post['fileupload'];
                $session->offsetSet('current_website_id', $transcript->website_id);
                $transcriptTable->saveTranscript($transcript);    // updating the data
                return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
            } else {
                $filename = getcwd() . '\module\Clients\data\uploads\\' . $transcript->website_id . '\\' . $file_name;
                unlink($filename);        // delete the old uploaded files
                // upload new file
                $uploadPath = getcwd() . '\module\Clients\data\uploads\\' . $transcript->website_id;

                $post = array_merge_recursive(
                        $this->request->getPost()->toArray(), array('fileupload' => $uploadFile['name'])
                );

                $adapter->setDestination($uploadPath);
                if ($adapter->receive($uploadFile['name'])) {   //if file is received in uploaded folder
                    //saving Client data table
                    $transcript = $transcriptTable->getTranscript($post['id']);
                    $form->bind($transcript);
                    $form->setData($post);

                    $post['date_posted'] = date("Y-m-d", strtotime($post['date_posted']));
                    $post['date_received'] = date("Y-m-d", strtotime($post['date_received']));
                    $post['date_revised'] = date("Y-m-d", strtotime($post['date_revised']));
                    $transcript->date_posted = $post['date_posted'];
                    $transcript->date_revised = $post['date_revised'];
                    $transcript->date_received = $post['date_received'];
                    $transcript->name = $post['name'];
                    $transcript->fileupload = $post['fileupload'];
                    $session->offsetSet('current_website_id', $transcript->website_id);
                    $transcriptTable->saveTranscript($transcript);    // updating the data
                    return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
                } else {
                    print_r("Could not get file in uploads folder");
                    exit();
                }
            }
        }

        // changing date formation
        $transcript->date_posted = date("m/d/Y", strtotime($transcript->date_posted));
        $transcript->date_received = date("m/d/Y", strtotime($transcript->date_received));
        $transcript->date_revised = date("m/d/Y", strtotime($transcript->date_revised));
        $form->bind($transcript); //biding data to form

        $viewModel = new ViewModel(array(
            'form' => $form,
            'id' => $this->params()->fromRoute('id'),
            'fileupload' => $transcript->fileupload,
        ));
        return $viewModel;
    }

    public function deleteAction() {  // delete transcript
        header('Content-Type: application/json');
        $current_website_id = $_POST['current_website'];
//            print_r($_POST['current_website']);exit;
        $id = (int) $this->params()->fromRoute('id', 0);
//                    Debug::dump($id);exit;
        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        //delete Transcript for a client website
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);
        $data = $transcriptTable->getTranscript($id);

        $filename = getcwd() . '\module\Clients\data\uploads\\' . $current_website_id . '\\' . $data->fileupload;
//        print_r($filename);exit;
        unlink($filename);        // delete the old uploaded files
        $transcriptTable->deleteTranscript($id);


        echo json_encode(array('data' => ''));
        exit();
    }

    public function downloadallAction() {
//       header('Content-Type: application/json');
//       print($_POST['downloadids']);exit;
        $download_ids = $_POST['downloadids'];
        $current_website_id = (int) $this->params()->fromRoute('id', 0);
        if (!$current_website_id) {
            print_r("Cant get id in download ALL action");
            exit();
        }
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);

        if (!file_exists(getcwd() . '\module\Clients\data\uploads\temp\\' . $current_website_id)) {
            mkdir(getcwd() . '\module\Clients\data\uploads\temp\\', 0777, true);
        }
        if (!empty($download_ids)) {
            $download_ids = explode(",", $download_ids);
            foreach ($download_ids as $ids) {
                $single_data = $transcriptTable->getTranscript($ids);

                $filename = getcwd() . '\module\Clients\data\uploads\\' . $current_website_id . '\\' . $single_data->fileupload;
                $filename1 = getcwd() . '\module\Clients\data\uploads\temp\\'. $single_data->fileupload;
                copy($filename, $filename1);
            }
        } else {
            $data = $transcriptTable->getTranscriptWebsite($current_website_id);

            foreach ($data as $value) {
                $filename = getcwd() . '\module\Clients\data\uploads\\' . $current_website_id . '\\' . $value['fileupload'];
                $filename1 = getcwd() . '\module\Clients\data\uploads\temp\\' . $value['fileupload'];
                copy($filename, $filename1);
            }
        }
        $filter = new \Zend\Filter\Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => 'transcript.zip'
            ),
        ));
        $compressed = $filter->filter(getcwd() . '\module\Clients\data\uploads\temp');

                $files = glob(getcwd() . '\module\Clients\data\uploads\temp\*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }
        if (is_dir(getcwd() . '\module\Clients\data\uploads\temp')) {
            if (!rmdir(getcwd() . '\module\Clients\data\uploads\temp')) { {
                    echo ("Could not remove");
                    exit;
                }
            }
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=transcript.zip');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($compressed)); // $file));
        ob_clean();
        flush();
        // readfile($file);
        readfile($compressed);
        //        print_r($files);exit;


        exit;
    }

    public function downloadAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        $session = new Container('transcript');
        $current_website_id = $session->offsetGet('transcript_website_id');
//         print_r($current_website_id);exit();
        if (!$id) {
            print_r("Cant get id in download action");
            exit();
        }
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);
        $data = $transcriptTable->getTranscript($id);
        $filename = getcwd() . '\module\Clients\data\uploads\\' . $current_website_id . '\\' . $data->fileupload;
        if (file_exists($filename)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($data->fileupload));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filename)); // $file));
            ob_clean();
            flush();
            // readfile($file);
            readfile($filename);
            exit;
        }

        return new ViewModel(array());
    }

    public function getTranscriptByIdAction() {
        header('Content-Type: application/json');
        $id = (int) $this->params()->fromRoute('id', 0);

        if (!$id) {
            return $this->redirect()->toRoute(NULL, array(
                        'controller' => 'index',
                        'action' => 'list'
            ));
        }
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);
        $data = $transcriptTable->getTranscriptWebsite($id);

//         Debug::dump($value->url);exit;

        echo json_encode(array('data' => (array) $data));
        exit();
    }

    public function setDateRange() {
        $session = new Container('transcript');
        $from = $session->offsetGet('from');
        $till = $session->offsetGet('till');
        $website_id = $session->offsetGet('current_website_id');
        $from = $from . ' 00:00:00';
        $till = $till . ' 23:59:59';
//       print_r($from);
//       print_r($till);exit;
        $tableGateway = $this->getConnection();
        $transcriptTable = new TranscriptTable($tableGateway);
        $website_transcripts_data = $transcriptTable->dateRange($from, $till, $website_id);
        return $website_transcripts_data;
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
        $session = new Container('transcript');
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('from', $all_ranges[0]);
        $session->offsetSet('till', $all_ranges[1]);
        $session->offsetSet('daterange', $daterange);
        $transcript_client_id = $session->offsetGet('transcript_client_id');
        return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
    }

    public function setmessageAction() {  // set message for delete client transcript
        $session = new Container('transcript');
        $transcript_client_id = $session->offsetGet('transcript_client_id');
        $website_id = (int) $this->params()->fromRoute('id', 0);
        $session->offsetSet('current_website_id', $website_id);
        $session->offsetSet('msg', "Transcript has been successfully Deleted.");
//        print_r($website_id);exit;
        return $this->redirect()->toUrl('/transcript/index/' . $transcript_client_id);
    }

    public function getConnection() {           // set connection to transcript table
        $sm = $this->getServiceLocator();
        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        $resultSetPrototype = new \Zend\Db\ResultSet\ResultSet();
        $resultSetPrototype->setArrayObjectPrototype(new
                \Clients\Model\Transcript);
        $tableGateway = new \Zend\Db\TableGateway\TableGateway('transcripts', $dbAdapter, null, $resultSetPrototype);
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