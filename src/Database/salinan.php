<?php

namespace Lotus\Framework;

define('LOTUS_OBJECT','OBJECT',true);

class Database
{

	private $query_select = array();

	private $queryString = '';

	private $query_where = array();

	private $query_from = array(); 
	
	private $query_where_string = array();

	private $distinct = false;

	private $limit = false;

	private $offset = false;

	private $row_count = false;

	private $query_orderBy = array();

	private $query_groupBy = array();

	private $query_join = array();
	//ARRAY_N,ARRAY_A,LOTUS_OBJECT
	private $result_type = LOTUS_OBJECT;

	private $old_query = '';

	private $connection;

	function __construct(){

		global $wpdb;

		$this->connection = $wpdb;
			
	}

	function bind($param,$value){

		if($param=='?')
			$this->queryString = preg_replace('/?/', $value, $this->queryString, 1);
		else
			$this->queryString = str_replace($param,$value,$this->queryString);
	
	}

	/*
	 * Where Query Builder
	 * 
	 * $this->db->where('name', $name,'=','AND');
	 * $this->db->where('age', $age,'=','AND',false);
	 *
	 */

	// Match
	function where($column,$value,$operator='=',$condition='AND'){

		$this->pushWhere($column,$value,$condition,$operator);

	}

	function orWhere($column,$value,$operator='='){
		
		$condition='OR';
		$this->pushWhere($column,$value,$condition,$operator);
	}

	// Like
	function like($column,$value,$condition = 'AND'){

		$operator='LIKE';
		
		$this->pushWhere($column,$value,$condition,$operator);
	}

	function orLike($column,$value){
		$operator='LIKE';
		
		$condition = 'OR';

		$this->pushWhere($column,$value,$condition,$operator);
	}

	// NOT LIKE
	function notLike($column,$value,$condition = 'AND'){

		$operator='NOT LIKE';
	
		$this->pushWhere($column,$value,$condition,$operator);
	}

	function orNotLike($column,$value){
		$operator='NOT LIKE';
	
		$condition = 'OR';

		$this->pushWhere($column,$value,$condition,$operator);
	}

	// The custom where query will be ALWAYS put in end of the query builder
	function whereQuery($query,$condition='AND'){

		$query = trim($query);

		$search = array ("/^\(/","/\)$/");
		$replace = array ('','');

		$query = preg_replace($search, $replace, $query);

		$query = array($query,$condition);

		array_push($this->query_where_string,$query);
	}

	private function pushWhere($column,$value,$condition,$operator){

		$operator = strtoupper($operator);

		if($operator=='AND'){
			array_unshift($this->query_where,array($column,$value,$condition,$operator));
		}
		else{
			array_push($this->query_where, array($column,$value,$condition,$operator));
		}

	}



	// Produce select distinct
	function distinct(){
		$this->distinct=true;
	}

	//limit Query 
	function limit($offset,$limit=false){
		$this->offset=$offset;
		$this->limit=$limit;
	}

	function setResultType($result_type){

		$expected_type = array(ARRAY_A,ARRAY_N,LOTUS_OBJECT);

		if(!in_array($result_type, $expected_type)){
			$result_type = LOTUS_OBJECT;
		}

		$this->result_type = $result_type;
	}

	// function customQuery($string){

	// 	$this->queryString = $string;

	// 	$result = $this->connection->get_results($this->queryString,$this->result_type);
	// 	restore_error_handler();

	// 	$this->resetQuery();

	// 	if(is_null($result))
	// 		$result = array();

	// 	return $result;
	// }

	function get($table='',$offset=false,$limit=false){

		$offset = intval($offset);
		$limit = intval($limit);

		if($table!='')
			$this->from($table);

		$this->generateQuery();
		$this->generateSelect();
		if(is_int($offset)&&!is_int($limit)){

			$this->limit($offset);
		}
		else if(is_int($offset)&&is_int($limit)){

			$this->limit($offset,$limit);
		}
		$this->generateOffset();

		$result = $this->connection->get_results($this->queryString,$this->result_type);

		$this->checkError();
		
		if(sizeof($result)==0)
			$result = array();
		
		$this->resetQuery();
		
		return $result;
	}	

	function execute($query='') {
		return $this->getRow($query);
	}


	function getRow($query='') {

		if(!$query) {
	
			$this->from($table);
			$this->generateQuery();
			$this->generateSelect();
	
		}
		else {

			$this->queryString=$query;
		}

		

		$result = $this->connection->get_results($this->queryString,$this->result_type);
	
		$this->checkError();

		if(sizeof($result)==0)
			$result = false;
		else
			$result = $result[0];

		$this->resetQuery();

		return $result;
	}	


	function getResults($query){
		$result = $this->connection->get_results($query);

		$this->queryString=$query;

		$this->checkError();
		
		if(sizeof($result)==0)
			$result = array();

		$this->resetQuery();

		return $result;
	}

	// function getRow($query){
	
	// 	$result = $this->connection->get_row($query);

	// 	$this->queryString=$query;

	// 	$this->checkError();
		
	// 	if(sizeof($result)==0)
	// 		$result = array();

	// 	$this->resetQuery();

	// 	return $result;
	// }


	function insert($table,$update_data =array()){

		global $wpdb;
		global $wp;

		$result = false;

		$column = "";
		
		$insert_value = "";


		foreach ($update_data as $key => $value) {
			
			$local_value =array();

			$local_value[0] = '';

			if(!is_array($value)){

				$update_data=$value;
			}		
			else{
				//clean data


				$update_data = $this->clean($value[0],$value[1]);
			}

			$column="$column $key,";


			$insert_value="$insert_value $update_data,";
		}	

		$column = rtrim($column, ",");
		$insert_value = rtrim($insert_value, ",");

		//assume table is clean


		$this->queryString = "INSERT INTO $table ($column) VALUES ($insert_value)";

	
		$this->generateJoin();
		
		$this->generateWhere(); 
		
		$this->connection->query($this->queryString);
		
		$this->checkError();

		$this->resetQuery();

		return $this->connection->insert_id;
	}


	function insertQuery($table,$query){

		$result = false;

		//assume table is clean
		$this->queryString = "INSERT INTO $table query ";

		$this->generateJoin();
		
		$this->generateWhere();

		$result = $this->connection->query($this->queryString,$this->result_type);

		$this->checkError();

		$this->resetQuery();

		return $this->connection->insert_id;
	}



	function update($table,$update_data =array()){

		$result = false;

		$update_statement ='';

		foreach ($update_data as $key => $value) {
			
			$local_value =array();

			$local_value[0] = '';

			if(!is_array($value)){

				$update_data=$value;
			}		
			else{
				//clean data
				$update_data = $this->clean($value[0],$value[1]);
			}


			$update_statement="$update_statement $key = $update_data,";
		}	

		$update_statement = rtrim(	$update_statement,',');

		//assume table is clean
		$this->queryString = "UPDATE $table  ";

		$this->generateJoin();
		
		$this->queryString .= " SET $update_statement";

		$this->generateWhere();

		//run Update
		$this->connection->query($this->queryString);

		$this->checkError();

		$this->resetQuery();

		return true;

	}

	function updateQuery($table,$query){

		$result = false;

		//assume table is clean
		$this->queryString = "UPDATE $table SET $query ";

		$this->generateJoin();
		
		$this->generateWhere();

		//run Update
		$this->connection->query($this->queryString);

		$this->checkError();

		$this->resetQuery();

		return true;
	}


	function delete($table){

		$result = false;

		//assume table is clean
		$this->queryString = "DELETE $table.* FROM $table ";

		$this->generateJoin();

		$this->generateWhere();

		//run Update
		$this->connection->query($this->queryString);

		$this->checkError();

		$this->resetQuery();

		return true;
	}


	private function generateOffset(){

		$limit = $this->limit;
		$offset = $this->offset;

		if(!$limit&&!$offset){
			//do nothing
			return;	
		}

		if($offset&&!$limit){
			$this->queryString = "{$this->queryString} LIMIT $offset";
		}
		else{
			$this->queryString = "{$this->queryString} LIMIT $offset,$limit";
		}

	}

	function table($table){

		if(is_array($table))
			$this->query_from = array_merge($this->query_from, $table);
		else if(is_string($table))
			array_push($this->query_from, $table);

	}

	function orderBy($column,$order='DESC'){

		$string_orderBy = "$column $order";
		
		if(is_string($string_orderBy))
			array_push($this->query_orderBy, $string_orderBy);

	}

	function prepare($query) {
		$this->queryString = $query;
	}


	function query($query){
		$result = $this->connection->query($query);

		$this->queryString=$query;

		$this->checkError();
		
		return $result;
	}


	function groupBy($column){

		if(is_array($column))
			$this->query_groupBy = array_merge($this->query_groupBy, $column);
		
		if(is_string($column))
			array_push($this->query_groupBy, $column);

	}

	function selectCol($columns){

		if(is_array($columns))
			$this->query_select = array_merge($this->query_select, $columns);
		else if(is_string($columns))
			array_push($this->query_select, $columns);

	}

	function countAllResult($table=''){

		if($table!='')
			$this->from($table);
		$this->generateQuery();

		$this->queryString ="SELECT COUNT(*) as count {$this->queryString}";

		
		$result = $this->connection->get_results($this->queryString);

		$this->checkError();
	
		$this->resetQuery();
		
		if(is_null($result))
			return 0;
		else
			return $result[0]->count;
	}

	private function generateSelect(){
		
		//generate select
		if(sizeof($this->query_select)==''){
			$this->queryString = "SELECT * {$this->queryString}";

			return;
		}
		foreach ($this->query_select as $key=>$value) {

			if($key==0){
				$select_string = "$value";
			}
			else{
				$select_string .= ",$value";
			}
			
		}

		$this->queryString = "SELECT $select_string {$this->queryString}";
	}


	private function generateJoin(){
		foreach ($this->query_join as $key=>$value) {

			$this->queryString = "$this->queryString $value";

		}
	}

	// 	 $column = 'Room'
	//   $value = $id
	//   $condition = 'AND' | 'OR'
	//   $operator = NOT LIKE | LIKE | = | >= | <= | < | >	

	private function generateWhere(){
		//normal query generator
		$where = 'WHERE';

		foreach ($this->query_where as $key => $where_ar) {

			$column = $where_ar[0];
			$value = $where_ar[1];
			$operator = $where_ar[3];
			$condition = $where_ar[2];

			if($key==0)
				$this->queryString = "{$this->queryString} $where  $column $operator $value ";
			else
				$this->queryString = "{$this->queryString} $where  $condition $column $operator $value ";

			$where ='';
			
		}

		//the custom query is the only where query, remove the ( )
		$single_where=false;
		if(sizeof($this->query_where)==0&&sizeof($this->query_where_string)==1)
			$single_where = true;
		
		foreach ($this->query_where_string as $value) {

			if($single_where)
				$this->queryString = "{$this->queryString} $where {$value[0]}";
			
			else
				$this->queryString = "{$this->queryString} $where {$value[1]} ({$value[0]})";
			
			$where ='';
			$single_where=false;
		}

	}

	private function generateQuery(){


		//generate where query
		$from_string  ='';

		//1. FROM
		foreach ($this->query_from as $key=>$value) {

			if($key==0){
				$from_string = "$value";
			}
			else{
				$from_string .= ",$value";
			}
			
		}

		
		$this->queryString = "FROM $from_string {$this->queryString}";

		//2. Generate join string
		$this->generateJoin();

		//3. Generate where query i
		$this->generateWhere();


		//4. Generate Group by	

		$groupBy_string = '';

		foreach ($this->query_groupBy as $key=>$value) {

			if($key==0){
				$groupBy_string = "GROUP BY $value";
			}
			else{
				$groupBy_string .= ",$value";
			}
			
		}

		$this->queryString = "{$this->queryString} $groupBy_string";

		//5. Generate order by
		$orderBy_string = '';
		foreach ($this->query_orderBy as $key=>$value) {

			if($key==0){
				$orderBy_string = "ORDER BY $value";
			}
			else{
				$orderBy_string .= ",$value";
			}
			
		}

		$this->queryString = "{$this->queryString} $orderBy_string";


	}

	function join($table,$left_column,$right_column,$operator='=',$join_type='INNER'){
		$query = "$join_type JOIN $table on $left_column $operator $right_column";

		array_push($this->query_join, $query);	
	}

	function joinQuery($table,$query,$join_type='INNER'){
		$query = "$join_type JOIN $table on $query";

		array_push($this->query_join, $query);	
	}


	// Reset all query;
	function resetQuery(){

		$this->query_select = array();



		$this->query_where = array();

		$this->query_from = array(); 
		
		$this->query_where_string = array();

		$this->distinct = false;

		$this->limit = false;

		$this->offset = false;

		$this->row_count = false;

		$this->query_orderBy = array();

		$this->query_groupBy = array();

		$this->query_join = array();

		$this->result_type = LOTUS_OBJECT ;

		$this->old_query = $this->queryString;

		$this->queryString= '';
	}

	function getQuery() {
		return $this->queryString;
	}

	function lastQuery() {

		return $this->old_query;
	}

	//@todo
	private function checkError() {

		global $EZSQL_ERROR;

		if ( $EZSQL_ERROR )
		{

			//if debug is on 
			if(__c('debug')){
				l_displayMessage('Database Error',"<p> Please check your query :</p><br><p><b>{$this->queryString}</b></p><br><p>{$EZSQL_ERROR[0]['error_str']}</p>",'notice');
			}
			else{
				l_displayMessage('Database Error',"Something went wrong with the databse",'notice');
			}
			// debug off : kill the script
			die();
		}
		else
		{
		    return false;
		}

	}

	function prefix() {
		return $this->connection->prefix ;
	}

	/*
	 * s,F,d
	 */
	function clean($dirty_data,$type='s'){

		$type = strtolower($type);

		if($type!='s'&&$type!='d'&&$type!='F'){
			l_displayMessage('Clean data error',"<p> Please check your type :</p><p><b>{$type}</b></p><p>It must be : s,d or F</p>",'notice');
			die();
		}

		return $this->connection->prepare("%{$type}",$dirty_data);
	}



}