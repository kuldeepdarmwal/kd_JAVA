<?php
/**
 * CampaignModel
 * Holds method which help while Campaign's creation
 *
 */
class Frontend_model extends CI_Model{

	// call parent model's constructor
	function __construct(){
            parent::__construct() ;            
            $this->load->database();
            $this->load->model('Campaign_model','campaign',TRUE);
            $this->load->library('session'); 
	}
        
        //-----Campaign's Data retrieval methods ------
	
        /*
         *  update/insert matric data in matiric table
         *  @param int $coupon_id
         */
        public function update_matric($coupon_id){
            
            $matric = $this->campaign->get_matric_by_coupon_id($coupon_id)->row(); // get object of matric table row
            
            if(isset($matric->m_id)){
                $this->db->where('m_id', $matric->m_id)
                         ->update('metrics',array('impression' => ++$matric->impression));
                return $matric->m_id;
            }else{                
                $this->db->insert('metrics',array(
                            'coupon_id' => $coupon_id,
                            'impression' => '1'
                ));
                return $this->db->insert_id();
            }
        }
        
        /**
         *  Insert successfull claim with climer data
         *  @param object $claimer
         *  $claimer containt claimer email,matric_id,matric_id,email,method_id
         */
        public function inset_claim($claimer){
                $this->db->insert('claimer',array(
                        'metric_id'     => $claimer->matric_id,
                        'used_code_id'  => $claimer->used_code_id,
                        'email'         => $claimer->email,
                        'method_id'     => $claimer->method_id,
                        'ip'            => $_SERVER['REMOTE_ADDR'],
                        'date'          => time()
                ));                
        }
        
        /**
         *  Insert refrrer after successfull claim
         *  @param int $last_insert_id
         *  $last_insert_id id of last successfull insert claim.
         */
        public function insert_referrer($last_insert_id){
            //$this->db->insert_batch('referrer',$data);                        
            $email_data = $this->session->userdata('insert_referrer');
            if(is_array($email_data) && !empty($email_data) ){
                foreach($email_data as $val){
                    $this->db->insert('referrer',$val);
                    $this->db->insert('claimer_referrer',array('claimer_id'=>$last_insert_id,'referrer_id'=>$this->db->insert_id()));
                }
            }
            $email_data = $this->session->unset_userdata('insert_referrer');
        }
        
        /**
         *  find next coupon usecode
         *  @param int $coupon_id
         *  valid coupon id to find next usecode
         *  @return coupon usecode | false
         */
        public function next_use_code($coupon_id){
            $where_not_in =array();
            $claimed_usecode = $this->db->select('used_code_id')->get('claimer');
            
            foreach ($claimed_usecode->result() as $row){
                    $where_not_in[] = $row->used_code_id;
            }
            
            $this->db->select('id');
            $this->db->from('usecodes');
            $this->db->where('coupon_id =',$coupon_id);
            if(!empty($where_not_in)){
                $this->db->where_not_in('id', $where_not_in);
            }
            
            $result = $this->db->get();
            if($result->num_rows()) 
                return $result->row()->id;
            else return false;
        }
        
        /**
         *  Get coupon usecode by id
         *  @param int $id
         *  coupon valid usecode id
         *  @return coupon usecode
         */
        public function get_coupon_usecode($id){
            $this->db->select('coupon_use_code')
                     ->from('usecodes')
                     ->where('id =',$id);
            return $this->db->get()->row()->coupon_use_code;
        }
        
        /**
         *  Get total number of uses of a coupon.
         *  @param int $id
         *  valid coupon id
         *  @return number of uses
         */
        function get_total_use_code_count_by_id($id){
            $this->db->select('id')
                     ->from('claimer')
                     ->join('metrics', 'claimer.metric_id = metrics.m_id','left')
                     ->where('metrics.coupon_id',$id);
            return $this->db->get()->num_rows();
        }
        
        /**
         *  Check coupon claim against claimer email id
         *  @param string $email
         *  claimer email id
         *  @return 1|0
         */
        public function check_claim($email){
            $this->db->select('id')
                     ->from('claimer')
                     ->join('metrics', 'claimer.metric_id = metrics.m_id')
                     ->where('claimer.email', $email)
                     ->where('metrics.coupon_id',$this->session->userdata('coupon_id'));            
            return $this->db->get()->num_rows();
        }
        
        /**
         * Process coupon claim insert claim and refrrer
         * @param int $coupon_id
         * coupon id
         * @param int $method_id
         * claim method id
         * @param string $mode
         * mode of claim either test or live 
         * @return usecode id
         */
        public function process_claim($coupon_id,$method_id,$mode){
            $matric_data = $this->session->userdata('matric'); // get matric data from session
            
            $claimer->used_code_id = $this->next_use_code($coupon_id); // get coupon usecode id            
            $claimer->matric_id = $matric_data[$coupon_id]['matric_id'];
            $claimer->method_id = $method_id;
            $claimer->coupon_id = $coupon_id;
            $claimer->email = $this->session->userdata('claimer_email'); // get claimer email from session
            $this->session->unset_userdata('claimer_email');
           
            if( $mode != 'test'){
                $this->inset_claim($claimer);            
                $this->insert_referrer($this->db->insert_id());
            }            
            return $claimer->used_code_id;
        }
        
        /**
         * Set coupon data
         * @param object $campaign
         * object of campain by id
         * @return campaign with coupon show stats
         */
        public function show_coupon($campaign){
            $campaign->coupon_usecode = $this->get_coupon_usecode($campaign->claimer->used_code_id);
            $campaign->expire = $campaign->created + (86400 * (int) $campaign->validity );
            $campaign->expire = date('m/d/y',$campaign->expire);
            $campaign->show_coupon_use_code = true;
            return $campaign;
        }
        
}