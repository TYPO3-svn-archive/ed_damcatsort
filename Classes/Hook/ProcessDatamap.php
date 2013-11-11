<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Nikola Stojiljkovic <nikola.stojiljkovic(at)essentialdots.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class Tx_EdDamcatsort_Hook_ProcessDatamap {

	/**
	 * @var string
	 */
	protected $category;

	/*
	 * core: processDatamap_preProcessFieldArray hook function
	 */
	public function processDatamap_preProcessFieldArray($incomingFieldArray, $table, $id, $parent) {
	}
	
	/*
	 * core: processDatamap_postProcessFieldArray hook function
	 */
	public function processDatamap_postProcessFieldArray ($status, $table, $id, $fieldArray, $parent) {
		if ($table=='tx_dam') {
			if ($fieldArray['category']) {
					//there was a change in categories
				if (substr($parent->datamap['tx_dam'][$id]['category'], -1)==",") {
					$category = substr($parent->datamap['tx_dam'][$id]['category'], 0, -1);
				} else {
					$category = $parent->datamap['tx_dam'][$id]['category'];
				}
			}
			
			if (strlen($category)==0) {
				$category = "0";
			}

			$this->category = $category;
		}
	}

	/**
	 * This method is called by a hook in the TYPO3 Core Engine (TCEmain) when a create or update action is performed on a record.
	 *
	 * @param	string $status Operation status
	 * @param	string $table The table TCEmain is currently processing
	 * @param	string $rawId The records id (if any)
	 * @param	array $fieldArray The field names and their values to be processed (passed by reference)
	 * @param	t3lib_TCEmain $pObj  Reference to the parent object (TCEmain)
	 * @return	void
	 * @access public
	 */
	public function processDatamap_afterDatabaseOperations($status, $table, $rawId, $fieldArray, $pObj) {

		if ($table=='tx_dam') {
			if (t3lib_utility_Math::canBeInterpretedAsInteger($rawId)) {
				$id = $rawId;
			} else {
				$id = $pObj->substNEWwithIDs[$rawId];
			}

			$sql = "DELETE FROM tx_eddamcatsort_media WHERE category NOT IN (".$this->category.") AND dam = ".$id;

			$res = $GLOBALS['TYPO3_DB']->sql_query($sql);

			$sql = "SELECT d.pid, c.uid as category, d.uid as dam
			          FROM tx_dam_mm_cat mm
			               INNER JOIN tx_dam d ON (d.uid = mm.uid_local and d.uid = ".$id.")
			               INNER JOIN tx_dam_cat c ON (c.uid = mm.uid_foreign)
			               LEFT  JOIN tx_eddamcatsort_media m ON (mm.uid_local = m.dam AND mm.uid_foreign = m.category)
			         WHERE m.uid IS NULL";

			$res = $GLOBALS['TYPO3_DB']->sql_query($sql);

			$rows = array();

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$rows[] = $row;
			}

			foreach ($rows as $row) {
				$sorting = $pObj->getSortNumber('tx_eddamcatsort_media',0,$row['pid']);
				$sql = "INSERT INTO tx_eddamcatsort_media (pid, category, dam, sorting) VALUES (".$row['pid'].", ".$row['category'].", ".$row['dam'].", ".$sorting.")";
				$res = $GLOBALS['TYPO3_DB']->sql_query($sql);
			}
		}
	}
}

?>