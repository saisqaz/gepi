<?php

/*
*
* Copyright 2016 Régis Bouguin
*
* This file is part of GEPI.
*
* GEPI is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* GEPI is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with GEPI; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 
*/

function enregistreMEF() {
	global $mysqli;
	$classeBase = filter_input(INPUT_POST, 'classeBase', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	$classeMEF = filter_input(INPUT_POST, 'mefAppartenance', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
	foreach ($classeBase as $key=>$value) {
		$sqlSaveMEF = "UPDATE classes SET mef_code = '$classeMEF[$key]' WHERE id = $key ";
		//echo $sqlSaveMEF.'<br>';
		$mysqli->query($sqlSaveMEF);	
	}
}

/**
 * Renvoie la première partie de l'année scolaire au format complet (2009/2010 ou 2009/10 ou 2009-2010 …)
 * 
 * @param String $_annee année scolaire
 * @return int début de l'année scolaire
 */
function LSUN_annee($_annee) {
	$expl = preg_split("/[^0-9]/", $_annee);
	$retour = intval($expl[0]);
	//$retour = ($expl[0]);
	return $retour;
}

/**
 * Récupère les données de la table utilisateurs
 *   
 * @global type $mysqli
 * @param String $login Login de l'utilisateur
 * @return Object mysqliQuery
 */
function getUtilisateur($login) {
	global $mysqli;
	$sql = "SELECT * FROM `utilisateurs` WHERE login = '".$login."' ";
	//echo "<br />".$sql."<br />";
	$resultchargeDB = $mysqli->query($sql);	
	return $resultchargeDB->fetch_object();	
}

/**
 * Récupère les utilisateurs sur le statut
 * 
 * @global type $mysqli
 * @param string $statut statut recherché
 * @return type
 */
function getUtilisateurSurStatut($statut = "%") {
	global $mysqli;
	$sql = "SELECT login, nom, prenom FROM utilisateurs WHERE statut LIKE '".$statut."' ";
	//echo $sql;
	$resultchargeDB = $mysqli->query($sql);	
	return $resultchargeDB;		
}

/**
 * Récupère id, nom, prenom civilité d'un responsable du livret
 * 
 * @global type $mysqli
 * @return type
 */
function getResponsables() {
	global $mysqli;
	$sql = "SELECT r.id, r.login, u.nom, u.prenom, u.civilite FROM lsun_responsables AS r "
		. "INNER JOIN utilisateurs AS u "
		. "ON u.login = r.login ";
	//echo $sql;
	$resultchargeDB = $mysqli->query($sql);	
	return $resultchargeDB;		
}

/**
 * Récupçre les périodes
 * 
 * @global type $mysqli
 * @param type $myData id des classes 
 * @return type
 */
function getPeriodes($myData = NULL) {
	global $mysqli;
	$sqlPeriodes = "SELECT DISTINCT num_periode FROM periodes ";
	if ($myData) {
		$sqlPeriodes .=  "WHERE id_classe IN (".$myData.") ";
	}
	$sqlPeriodes .=  "ORDER BY num_periode ";
	//echo $sqlPeriodes;
	$resultchargeDB = $mysqli->query($sqlPeriodes);	
	return $resultchargeDB;	
}

/**
 * Renvoie id, classe, nom_complet  d'une classe
 * 
 * @global type $mysqli
 * @param type $classeId
 * @return type
 */
function getClasses($classeId = NULL) {
	global $mysqli;
	$sqlClasses = "SELECT DISTINCT id, classe, nom_complet, mef_code FROM classes ";
	if ($classeId) {$sqlClasses .= " WHERE id = '$classeId' ";}
	$sqlClasses .= " ORDER BY classe ";
	//echo $sqlClasses;
	$resultchargeDB = $mysqli->query($sqlClasses);	
	return $resultchargeDB;	
}

/**
 * Enregistre les données d'un parcours
 * 
 * @global type $mysqli
 * @param type $newParcoursTrim
 * @param type $newParcoursClasse
 * @param type $newParcoursCode
 * @param type $newParcoursTexte
 * @param type $newParcoursId
 * @return type
 */
function creeParcours($newParcoursTrim, $newParcoursClasse, $newParcoursCode, $newParcoursTexte, $newParcoursId = '') {
	global $mysqli;
	$sqlNewParcours = "INSERT INTO lsun_parcours_communs (id,periode,classe,codeParcours,description)  VALUES ('$newParcoursId', '$newParcoursTrim', '$newParcoursClasse', '$newParcoursCode', '$newParcoursTexte') ON DUPLICATE KEY UPDATE periode = '$newParcoursTrim',classe = '$newParcoursClasse',codeParcours = '$newParcoursCode',description = '$newParcoursTexte' ";
	//echo $sqlNewParcours;
	$resultchargeDB = $mysqli->query($sqlNewParcours);	
	return $resultchargeDB;	
}

/**
 * Récupère un parcours
 * 
 * @global type $mysqli
 * @global type $selectionClasse
 * @param type $parcoursId
 * @param type $classe
 * @param type $periode
 * @return type
 */
function getParcoursCommuns($parcoursId = NULL, $classe = NULL, $periode = NULL) {
	global $mysqli;
	global $selectionClasse;
	$myData = implode(",", $selectionClasse);
	$sqlGetParcours = "SELECT * FROM lsun_parcours_communs WHERE classe IN ($myData) ";
	if($parcoursId || $classe || $periode) {
		$sqlGetParcours .= setFiltreParcoursCommuns($parcoursId, $classe, $periode);
		//echo $sqlGetParcours;
	}
	$sqlGetParcours .= " ORDER BY classe, periode, codeParcours ";
	//echo $sqlGetParcours;
	$resultchargeDB = $mysqli->query($sqlGetParcours);	
	return $resultchargeDB;	
}

/**
 * Filtre pour récupérer un parcours
 * 
 * @param type $parcoursId
 * @param type $classe
 * @param type $periode
 * @return type
 */
function setFiltreParcoursCommuns($parcoursId, $classe, $periode) {
	$sqlGetParcours = " AND ";
	if ($parcoursId) {
		$sqlGetParcours .= " id = '$parcoursId' ";
		if ($classe || $periode) {
			$sqlGetParcours .= " AND ";
		}
	}
	if ($classe) {
		$sqlGetParcours .= " classe = '$classe' ";
		if ($periode) {
			$sqlGetParcours .= " AND ";
		}
	}
	if ($periode) {$sqlGetParcours .= " periode = '$periode' ";}
	return $sqlGetParcours;
}

/**
 * Suppression d'un parcours
 * 
 * @global type $mysqli
 * @param type $deleteParcours
 */
function supprimeParcours($deleteParcours) {
	global $mysqli;
	$sqlDelParcours = "DELETE FROM lsun_parcours_communs WHERE id = $deleteParcours ";
	//echo $sqlDelParcours;
	$mysqli->query($sqlDelParcours);
}

/**
 * Modification d'un parcours
 * 
 * @global type $mysqli
 * @param type $modifieParcoursId
 * @param type $modifieParcoursCode
 * @param type $modifieParcoursTexte
 */
function modifieParcours($modifieParcoursId, $modifieParcoursCode, $modifieParcoursTexte) {
	global $mysqli;
	$sqlModifieParcours = "UPDATE lsun_parcours_communs "
		. "SET codeParcours = '$modifieParcoursCode', description = '$modifieParcoursTexte' "
		. "WHERE id = '$modifieParcoursId' ";
	//echo $sqlModifieParcours;
	$mysqli->query($sqlModifieParcours);
}

/**
 * 
 * @global type $mysqli
 * @param type $mefClasse
 * @return type
 */
function getMatiereLSUN($mefClasse = NULL) {
	global $mysqli;
	//$sqlMatieres = "SELECT * FROM matieres ORDER BY nom_complet";
	
	 $sqlMatieres = "SELECT DISTINCT m.*, mm.code_modalite_elect FROM mef_matieres AS mm INNER JOIN matieres AS m ON mm.code_matiere = m.code_matiere ";
	 if ($mefClasse) {
		 $sqlMatieres .= "WHERE mef_code = $mefClasse ";
	 }
	$sqlMatieres .= " ORDER BY matiere , code_modalite_elect DESC ";
	// echo $sqlMatieres;
	$resultchargeDB = $mysqli->query($sqlMatieres);
	return $resultchargeDB;
}

/**
 * Enregistre un EPI
 * 
 * @global type $mysqli
 * @param type $newEpiPeriode
 * @param type $newEpiClasse
 * @param type $newEpiCode
 * @param type $newEpiIntitule
 * @param type $newEpiDescription
 * @param type $newEpiMatiere
 * @param type $modifieEPIMatiereModalite
 * @param type $idEpi
 */
function sauveEPI($newEpiPeriode, $newEpiClasse, $newEpiCode, $newEpiIntitule, $newEpiDescription, $newEpiMatiere, $idEpi = NULL) {
	global $mysqli;
	
	$sqlCreeEpi = "INSERT INTO lsun_epi_communs (id, periode, codeEPI, intituleEpi, descriptionEpi) VALUES (";
	
	if ($idEpi) {
		$sqlCreeEpi .= $idEpi;
		delMatiereEPI($idEpi);
		delLienEPI($idEpi);
	}
	else {$sqlCreeEpi .= "NULL";}
	
	$sqlCreeEpi .= ", '$newEpiPeriode', '$newEpiCode', \"".htmlspecialchars($newEpiIntitule)."\", \"".htmlspecialchars($newEpiDescription)."\") "
		. "ON DUPLICATE KEY UPDATE periode = \"".$newEpiPeriode."\", codeEPI = \"".$newEpiCode."\", intituleEpi = \"".htmlspecialchars($newEpiIntitule)."\", descriptionEpi = \"".htmlspecialchars($newEpiDescription)."\" ";
	// echo $sqlCreeEpi.'<br>';
	$mysqli->query($sqlCreeEpi);
	$idEPI = getIdEPI($newEpiPeriode, $newEpiCode, $newEpiIntitule, htmlspecialchars($newEpiDescription))->fetch_object()->id;
	
	if ($newEpiMatiere) {
		foreach ($newEpiMatiere AS $valeur) {
			$matiere = substr($valeur, 0, -1);
			$modalite = substr($valeur, -1);
			$sqlCreLienEPI = "INSERT INTO lsun_j_epi_matieres (id_matiere,  modalite, id_epi) VALUES ('$matiere', '$modalite' , $idEPI) ON DUPLICATE KEY UPDATE id_matiere = '$matiere' , modalite = '$modalite' ";
			//echo $sqlCreLienEPI;
			$mysqli->query($sqlCreLienEPI);
		}
	}
	
	if ($newEpiClasse) {
		foreach ($newEpiClasse AS $valeur) {
			$sqlCrejoinEpiClasse = "INSERT INTO lsun_j_epi_classes (id_epi, id_classe) VALUES ($idEPI , $valeur) ";
			//echo $sqlCrejoinEpiClasse.'<br>';
			$mysqli->query($sqlCrejoinEpiClasse);
		}
	}
}

/**
 * Réupère l'Id d'un EPI en fonction de ses caractéristiques
 * 
 * @global type $mysqli
 * @param type $newParcoursPeriode
 * @param type $newEpiClasse
 * @param type $newEpiCode
 * @param type $newEpiIntitule
 * @param type $newEpiDescription
 * @return type
 */
function getIdEPI($newParcoursPeriode, $newEpiCode, $newEpiIntitule, $newEpiDescription) {
	global $mysqli;
	$sqlGetIdEpi = "SELECT id FROM lsun_epi_communs WHERE "
		. "periode = '$newParcoursPeriode' AND "
		. "codeEPI = '$newEpiCode' AND "
		. "intituleEpi = \"$newEpiIntitule\" AND "
		. "descriptionEpi = \"$newEpiDescription\" ";
	// echo $sqlGetIdEpi;
	$resultchargeDB = $mysqli->query($sqlGetIdEpi);
	return $resultchargeDB;
}

/**
 * Retourne un EPI commun
 * 
 * @global type $mysqli
 * @global type $selectionClasse
 * @return type
 */
function getEPICommun() {
	global $mysqli;
	//global $selectionClasse;
	//$myData = implode(",", $selectionClasse);
	
	$sqlGetEpi = "SELECT lec.* FROM lsun_epi_communs AS lec "
		. "ORDER BY periode , codeEPI , id ";
	//echo $sqlGetEpi;
	$resultchargeDB = $mysqli->query($sqlGetEpi);
	return $resultchargeDB;
}

/**
 * Recherche les matières d'un EPI commun
 * 
 * @global type $mysqli
 * @param type $idEPI
 * @return type
 */
function getMatieresEPICommun($idEPI) {
	global $mysqli;
	$sqlGetMatieresEpi = "SELECT id_matiere, modalite FROM lsun_j_epi_matieres WHERE id_epi = '$idEPI' ";
	//echo $sqlGetMatieresEpi;
	$resultchargeDB = $mysqli->query($sqlGetMatieresEpi);
	return $resultchargeDB;
}

/**
 * Retourne une matière sur son nom court
 * 
 * @global type $mysqli
 * @param type $matiere
 * @return type
 */
function getMatiereOnMatiere($matiere) {
	global $mysqli;
	$sqlGetMatiereOnMatiere = "SELECT * FROM matieres WHERE matiere = '$matiere' ";
	//echo $sqlGetMatiereOnMatiere;
	$resultchargeDB = $mysqli->query($sqlGetMatiereOnMatiere);
	$retour = $resultchargeDB->fetch_object();
	return $retour;
}

/**
 * Supprime un EPI sur son Id
 * 
 * @global type $mysqli
 * @param type $EpiId
 */
function supprimeEPI($EpiId) {
	global $mysqli;	
	delMatiereEPI($EpiId);
	delClasseEPI($EpiId);
	delClasseEPI($EpiId);
	lsun_j_epi_enseignements($EpiId);
	$sqlDeleteEpi = "DELETE FROM lsun_epi_communs WHERE id = '$EpiId' ";
	$mysqli->query($sqlDeleteEpi);
	echo $sqlDeleteEpi.'<br>';
}

/**
 * Supprime les matières d'un EPI
 * 
 * @global type $mysqli
 * @param type $EpiId
 */
function delMatiereEPI($EpiId) {
	global $mysqli;
	$sqlDeleteJointureEpi = "DELETE FROM lsun_j_epi_matieres WHERE id_epi = '$EpiId' ";
	//echo $sqlDeleteJointureEpi.'<br>';
	$mysqli->query($sqlDeleteJointureEpi);
}

function getEpiAid() {
	global $mysqli;
	global $_EPI;
	$in = implode(",",$_SESSION['afficheClasse']);
	if ($in) {$in = ','.$in;}
	$in = '0'.$in;
	
	$sqlAidClasse = "SELECT "
		. "indice_aid AS id_enseignement, indice_aid AS indice_aid, nom AS groupe , nom_complet AS description, NULL AS id_groupe,  NULL AS id_classe "
		. "FROM aid_config WHERE type_aid = $_EPI ";
		
	$resultchargeDB = $mysqli->query($sqlAidClasse);
	return $resultchargeDB;
}

function getEpiCours() {
	global $mysqli;
	global $_EPI;
	$in = implode(",",$_SESSION['afficheClasse']);
	if ($in) {$in = ','.$in;}
	$in = '0'.$in;
	
	$sqlAidClasse = "SELECT t2.* , c.classe "
		. "FROM ( SELECT t1.* , jcg.id_classe AS toutesClasses "
		. "FROM ("
		. "SELECT jgm.id_matiere , t0.* FROM "
		. "(SELECT jgt.id_groupe , jgc.id_classe FROM j_groupes_types AS jgt "
		. "INNER JOIN j_groupes_classes AS jgc ON jgc.id_groupe = jgt.id_groupe "
		. "WHERE jgt.id_type = $_EPI AND jgc.id_classe IN ($in)) AS t0 "
		. "INNER JOIN j_groupes_matieres AS jgm ON jgm.id_groupe = t0.id_groupe"
		. ""
		. ") AS t1 "
		. "INNER JOIN j_groupes_classes AS jcg ON jcg.id_groupe = t1.id_groupe ) "
		. "AS t2 "
		. "INNER JOIN classes AS c ON t2.toutesClasses = c.id";
	
	//echo '<br>--*--<br>'.$sqlAidClasse.'<br>--*--<br>';
	$resultchargeDB = $mysqli->query($sqlAidClasse);
	return $resultchargeDB;
}

function lieEpiCours($id_epi , $id_enseignement , $aid, $id=NULL) {
	global $mysqli;
	$sqLieEpiCours = "INSERT INTO lsun_j_epi_enseignements (id , id_epi , id_enseignements , aid) VALUES (";
	if ($id) {
		$sqLieEpiCours .= $id;
	}	else {
		$sqLieEpiCours .= "NULL";
	}
	$sqLieEpiCours .= ",$id_epi , $id_enseignement , $aid)";
	//echo $sqLieEpiCours;
	$mysqli->query($sqLieEpiCours);
}

function getLiaisonEpiEnseignementByIdEpi($id) {
	global $mysqli;
	$sqlGetLiaisonEpiEnseignement = "SELECT * FROM lsun_j_epi_enseignements WHERE id_epi = '$id' ";
	//echo $sqlGetLiaisonEpiEnseignement;
	$resultchargeDB = $mysqli->query($sqlGetLiaisonEpiEnseignement);
	return $resultchargeDB;
}

function getAID($id) {
	global $mysqli;
	$sqlGetAid = "SELECT * FROM aid_config WHERE indice_aid = '$id' AND type_aid = '2' ";
	//echo $sqlGetAid;
	$retour = $mysqli->query($sqlGetAid)->fetch_object();
	return $retour;
}

function getCoursById($id) {
	global $mysqli;
	
	$sqlGetCours = "SELECT DISTINCT t1.*, c.classe FROM "
		. "(SELECT t0.* , jgc.id_classe FROM "
		. "(SELECT jgt.*,jgm.id_matiere FROM j_groupes_types AS jgt "
		. "INNER JOIN j_groupes_matieres AS jgm ON jgt.id_groupe = jgm.id_groupe "
		. "WHERE jgt.id_groupe = '$id') AS t0 "
		. "INNER JOIN j_groupes_classes AS jgc ON jgc.id_groupe = t0.id_groupe) AS t1 "
		. "INNER JOIN classes AS c ON c.id = t1.id_classe "
		. "ORDER BY id_groupe ";
	
	//echo '<br><br>'.$sqlGetCours.'<br><br>';
	$retour = $mysqli->query($sqlGetCours);
	return $retour;
}

function existeLienAID($id_classe, $id_enseignements) {
	global $mysqli;
	$retour = FALSE;
	$sqlGetExisteLien = "SELECT 1=1 FROM lsun_j_epi_enseignements AS lsje "
		. " INNER JOIN lsun_epi_communs AS lec ON lec.id = lsje.id_epi "
		. " WHERE lsje.id_enseignements='$id_enseignements' AND aid='1' AND lec.classe ='$id_classe' ";
	//echo $sqlGetExisteLien;
	$resultchargeDB = $mysqli->query($sqlGetExisteLien);
	if ($resultchargeDB->num_rows) {
		$retour = TRUE;
	}
	return $retour;
}

function existeLienCours ($id_classe, $id_enseignements) {
	global $mysqli;
	$retour = FALSE;
	$sqlGetExisteLien = "SELECT 1=1 FROM lsun_j_epi_enseignements AS lsje "
		. " INNER JOIN lsun_epi_communs AS lec ON lec.id = lsje.id_epi "
		. "WHERE id_enseignements='$id_enseignements' AND aid='0' AND lec.classe ='$id_classe' ";
	//echo $sqlGetExisteLien;
	$resultchargeDB = $mysqli->query($sqlGetExisteLien);
	if ($resultchargeDB->num_rows) {
		$retour = TRUE;
	}
	return $retour;
}

function MefAppartenanceAbsent() {
	global $mysqli;
	$retour = FALSE;
	$sqlGetMefAppartenance = "SELECT 1=1 FROM classes WHERE mef_code = '' ";
	//echo $sqlGetMefAppartenance;
	if ($mysqli->query($sqlGetMefAppartenance)->num_rows) {
		$retour = TRUE;
	}
	return $retour;
}

function delLienEPI($idEPI) {
	global $mysqli;
	$sqlDelLienEPI = "DELETE FROM lsun_j_epi_enseignements WHERE id_epi = '$idEPI' ";
	//echo $sqlDelLienEPI;
	$mysqli->query($sqlDelLienEPI);
}

function getEpisGroupes($idEPI = NULL) {
	global $mysqli;
	$sqlEpisGroupes = "SELECT * FROM lsun_j_epi_enseignements ";
	if ($idEPI) {
		$sqlEpisGroupes .= "WHERE id_epi = $idEPI ";
	}
	$sqlEpisGroupes .= "ORDER BY id_epi ";
	//echo $sqlEpisGroupes;
	$resultchargeDB = $mysqli->query($sqlEpisGroupes);
	return $resultchargeDB;
}

function estClasseEPI($id_epi , $id_classe) {
	global $mysqli;
	$retour = FALSE;
	$sqlEpisClasse = "SELECT 1=1 FROM lsun_j_epi_classes WHERE id_classe = '$id_classe' AND id_epi = '$id_epi' ";
	//echo $sqlEpisClasse;
	if ($mysqli->query($sqlEpisClasse)->num_rows) {
		$retour = TRUE;
	}
	return $retour;
}

function estCoursEpi($id_epi , $id_cours) {
	global $mysqli;
	$retour = FALSE;
	$cours = explode('-', $id_cours);
	$sqlEpisCours= "SELECT 1=1 FROM lsun_j_epi_enseignements WHERE id_epi = '$id_epi' AND id_enseignements = '$cours[1]' ";	
	if ($mysqli->query($sqlEpisCours)->num_rows) {
		$retour = TRUE;
	}
	return $retour;
}

function delClasseEPI($EpiId) {
	global $mysqli;
	$sqlDelClasseEPI = "DELETE FROM lsun_j_epi_classes WHERE id_epi = '$EpiId' ";
	//echo $sqlDelClasseEPI;
	$mysqli->query($sqlDelClasseEPI);
	
}

function lsun_j_epi_enseignements($EpiId) {
	global $mysqli;
	$sqlDelEnseignement = "DELETE FROM lsun_j_epi_enseignements WHERE id_epi = '$EpiId' ";
	//echo $sqlDelEnseignement;
	$mysqli->query($sqlDelEnseignement);

}