/*
  Does not work independently.  You need to add this function to a model then create a controller funtion that calls it.  All you have to do is call the function and it will do the rest of the work, including printing the term, count and ids.  --Will
 */

	public function insert_heirarchy()
	{
		$sql = 'SELECT * FROM iab_categories WHERE 1';
		$query = $this->db->query($sql);
		if($query->result_array() > 0)
		{
			$iab_categories = $query->result_array();
			foreach($iab_categories as $v)
			{
				$temp_explode = explode(' > ', $v['tag_copy']);
				$temp_str = "";
				$temp_count = count($temp_explode) -1;
				foreach($temp_explode as $l => $w)
				{
					$term = $w;
					$descendant_id = $v['id'];
					print_r(array($descendant_id, $temp_count, $term));
					print "<br>";
					$sql = "INSERT IGNORE INTO iab_heirarchy (ancestor_id, descendant_id, path_length) SELECT id, ?, ? FROM iab_categories WHERE tag_friendly_name = ?";
					$query = $this->db->query($sql, array($descendant_id, $temp_count, $term));
					$temp_count--;
				}
			}
		}
		else
		{
			echo "no iab categories found";
			return;
		}
	}