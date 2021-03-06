<?php
/*
*/

// Initialisations diverses pour le module Discipline
$mod_disc_terme_incident=getSettingValue('mod_disc_terme_incident');
if($mod_disc_terme_incident=="") {$mod_disc_terme_incident="incident";}

$mod_disc_terme_sanction=getSettingValue('mod_disc_terme_sanction');
if($mod_disc_terme_sanction=="") {$mod_disc_terme_sanction="sanction";}

$mod_disc_terme_avertissement_fin_periode=getSettingValue('mod_disc_terme_avertissement_fin_periode');
if($mod_disc_terme_avertissement_fin_periode=="") {$mod_disc_terme_avertissement_fin_periode="avertissement de fin de période";}

if(preg_match("/^[AEIOUY]/i", ensure_ascii($mod_disc_terme_avertissement_fin_periode))) {
	$prefixe_mod_disc_terme_avertissement_fin_periode_de="d'";
	$prefixe_mod_disc_terme_avertissement_fin_periode_le="l'";
}
else {
	$prefixe_mod_disc_terme_avertissement_fin_periode_de="de ";
	$prefixe_mod_disc_terme_avertissement_fin_periode_le="le ";
}

// Paramètres concernant le délai avant affichage d'une infobulle via delais_afficher_div()
// Hauteur de la bande testée pour la position de la souris:
$hauteur_survol_infobulle=20;
// Largeur de la bande testée pour la position de la souris:
$largeur_survol_infobulle=100;
// Délais en ms avant affichage:
$delais_affichage_infobulle=500;

// Familles de sanctions:
$types_autorises=array('exclusion', 'retenue', 'travail', 'autre');

$discipline_droits_mkdir=getSettingValue('discipline_droits_mkdir');

$dossier_documents_discipline="documents/discipline";
if(((isset($multisite))&&($multisite=='y'))||(getSettingValue('multisite')=='y')) {
	if(isset($_COOKIE['RNE'])) {
		$dossier_documents_discipline.="_".$_COOKIE['RNE'];
		if(!file_exists("../$dossier_documents_discipline")) {
			if($discipline_droits_mkdir=="") {
				@mkdir("../$dossier_documents_discipline",0770);
			}
			else {
				@mkdir("../$dossier_documents_discipline");
			}
		}
	}
}

function p_nom($ele_login,$mode="pn") {
	$sql="SELECT * FROM eleves e WHERE e.login='".$ele_login."';";
	$res_ele=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res_ele)>0) {
		$lig_ele=mysqli_fetch_object($res_ele);
		if($mode=="pn") {
			return ucfirst(mb_strtolower($lig_ele->prenom))." ".mb_strtoupper($lig_ele->nom);
		}
		else {
			return mb_strtoupper($lig_ele->nom)." ".ucfirst(mb_strtolower($lig_ele->prenom));
		}
	}
	else {
		return "LOGIN INCONNU";
	}
}

function u_p_nom($u_login) {
	$sql="SELECT nom,prenom,civilite,statut FROM utilisateurs WHERE login='$u_login';";
	//echo "$sql<br />\n";
	$res3=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res3)>0) {
		$lig3=mysqli_fetch_object($res3);
		return $lig3->civilite." ".mb_strtoupper($lig3->nom)." ".ucfirst(mb_substr($lig3->prenom,0,1)).".";
	}
	else {
		return "LOGIN INCONNU";
	}
}

function get_lieu_from_id($id_lieu) {
	$sql="SELECT lieu FROM s_lieux_incidents WHERE id='$id_lieu';";
	$res_lieu_incident=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res_lieu_incident)>0) {
		$lig_lieu_incident=mysqli_fetch_object($res_lieu_incident);
		return $lig_lieu_incident->lieu;
	}
	else {
		return "";
	}
}

function formate_date_mysql($date){
	$tab_date=explode("/",$date);

	return $tab_date[2]."-".sprintf("%02d",$tab_date[1])."-".sprintf("%02d",$tab_date[0]);
}

function secondes_to_hms($secondes) {
	$h=floor($secondes/3600);
	$m=floor(($secondes-$h*3600)/60);
	$s=$secondes-$m*60-$h*3600;

	return sprintf("%02d",$h).":".sprintf("%02d",$m).":".sprintf("%02d",$s);
}

function infobulle_photo($eleve_login) {
	global $tabdiv_infobulle;

	$retour="";

	$sql="SELECT elenoet, nom, prenom, sexe FROM eleves WHERE login='$eleve_login';";
	$res_ele=mysqli_query($GLOBALS["mysqli"], $sql);
	$lig_ele=mysqli_fetch_object($res_ele);
	$eleve_elenoet=$lig_ele->elenoet;
	$eleve_nom=$lig_ele->nom;
	$eleve_prenom=$lig_ele->prenom;
	$eleve_sexe=$lig_ele->sexe;

	// Photo...
	$photo=nom_photo($eleve_elenoet);
	//$temoin_photo="";
	//if("$photo"!=""){
	if($photo){
		$titre="$eleve_nom $eleve_prenom";

		$texte="<div align='center'>\n";
		$texte.="<img src='".$photo."' width='150' alt=\"$eleve_nom $eleve_prenom\" />";
		$texte.="<br />\n";
		$texte.="</div>\n";

		$temoin_photo="y";

		$tabdiv_infobulle[]=creer_div_infobulle('photo_'.$eleve_login,$titre,"",$texte,"",14,0,'y','y','n','n',2000);

		$retour.=" <a href='#' onmouseover=\"delais_afficher_div('photo_$eleve_login','y',-100,20,1000,20,20);\"";
		$retour.=">";
		//$retour.="<img src='../images/icons/buddy.png' alt='$eleve_nom $eleve_prenom' />";
		$retour.="<img src='../mod_trombinoscopes/images/";
		if($eleve_sexe=="F") {
			$retour.="photo_f.png";
		}
		else {
			$retour.="photo_g.png";
		}
		$retour.="' class='icone20' alt='$eleve_nom $eleve_prenom' />";
		$retour.="</a>";
	}

	return $retour;
}

function affiche_mesures_incident($id_incident) {
	global $possibilite_prof_clore_incident;
	global $mesure_demandee_non_validee;
	global $dossier_documents_discipline;
	//global $exclusion_demandee_non_validee;
	//global $retenue_demandee_non_validee;

	$texte="";

	$sql="SELECT DISTINCT sti.login_ele FROM s_traitement_incident sti, s_mesures s WHERE sti.id_incident='$id_incident' AND sti.id_mesure=s.id AND s.type='prise'";
	$res_t_incident=mysqli_query($GLOBALS["mysqli"], $sql);
	$nb_login_ele_mesure_prise=mysqli_num_rows($res_t_incident);

	$sql="SELECT DISTINCT sti.login_ele FROM s_traitement_incident sti, s_mesures s WHERE sti.id_incident='$id_incident' AND sti.id_mesure=s.id AND s.type='demandee' ORDER BY login_ele";
	//$texte.="<br />$sql";
	$res_t_incident2=mysqli_query($GLOBALS["mysqli"], $sql);
	$nb_login_ele_mesure_demandee=mysqli_num_rows($res_t_incident2);

	if(($nb_login_ele_mesure_prise>0)||
		($nb_login_ele_mesure_demandee>0)) {
		$texte.="<table class='boireaus' summary='Mesures' style='margin:1px;'>\n";
	}

	if($nb_login_ele_mesure_prise>0) {
		$texte.="<tr class='lig-1'>\n";
		$texte.="<td style='font-size:x-small; vertical-align:top;'>\n";
		if(mysqli_num_rows($res_t_incident)==1) {
			$texte.="Mesure prise&nbsp;:";
		}
		else {
			$texte.="Mesures prises&nbsp;:";
		}
		$texte.="</td>\n";
		//$texte.="<td>";
		$texte.="<td style='font-size:x-small;'>\n";

			$texte.="<table class='boireaus'>\n";
			$cpt_tmp=0;
			while($lig_t_incident=mysqli_fetch_object($res_t_incident)) {
				$sql="SELECT * FROM s_traitement_incident sti, s_mesures s WHERE sti.id_incident='$id_incident' AND sti.id_mesure=s.id AND s.type='prise' AND login_ele='$lig_t_incident->login_ele' ORDER BY s.mesure;";
				$res_mes_ele=mysqli_query($GLOBALS["mysqli"], $sql);
				$nb_mes_ele=mysqli_num_rows($res_mes_ele);

				$texte.="<tr class='lig-1'>\n";
				$texte.="<td style='font-size:x-small;' rowspan='$nb_mes_ele'>\n";
				$texte.=p_nom($lig_t_incident->login_ele);
				$tmp_tab=get_class_from_ele_login($lig_t_incident->login_ele);
				if(isset($tmp_tab['liste_nbsp'])) {
					$texte.=" (<em>".$tmp_tab['liste_nbsp']."</em>)";
				}
				$texte.="</td>\n";
				$cpt_mes=0;
				while($lig_mes_ele=mysqli_fetch_object($res_mes_ele)) {
					if($cpt_mes>0) {$texte.="<tr>\n";}
					$texte.="<td style='font-size:x-small;'>";
					$texte.="$lig_mes_ele->mesure";
					$texte.="</td>\n";
					$texte.="</tr>\n";
					$cpt_mes++;
				}
				//$texte.="</tr>\n";
				$cpt_tmp++;
			}
			$texte.="</table>\n";

		$texte.="</td>\n";
		$texte.="</tr>\n";
	}

	//$possibilite_prof_clore_incident='y';
	if($nb_login_ele_mesure_demandee>0) {
		if($_SESSION['statut']=='professeur') {$possibilite_prof_clore_incident='n';}
		$texte.="<tr class='lig1'>";
		//$texte.="<td style='font-size:x-small; vertical-align:top;'>";
		//$texte.="<td style='font-size:x-small; vertical-align:top;' rowspan='".mysql_num_rows($res_t_incident2)."'>";
		$texte.="<td style='font-size:x-small; vertical-align:top;'>";
		if(mysqli_num_rows($res_t_incident2)==1) {
			$texte.="Mesure demandée&nbsp;:";
		}
		else {
			$texte.="Mesures demandées&nbsp;:";
		}
		$texte.="</td>";
		//$texte.="<td>";
		$texte.="<td style='font-size:x-small;'>\n";

			$texte.="<table class='boireaus'>\n";
			$cpt_tmp=0;
			while($lig_t_incident=mysqli_fetch_object($res_t_incident2)) {
				$sql="SELECT * FROM s_traitement_incident sti, s_mesures s WHERE sti.id_incident='$id_incident' AND sti.id_mesure=s.id AND s.type='demandee' AND login_ele='$lig_t_incident->login_ele' ORDER BY s.mesure;";
				$res_mes_ele=mysqli_query($GLOBALS["mysqli"], $sql);
				$nb_mes_ele=mysqli_num_rows($res_mes_ele);

				$texte.="<tr class='lig1'>\n";
				$texte.="<td style='font-size:x-small;' rowspan='$nb_mes_ele'>\n";
				$texte.=p_nom($lig_t_incident->login_ele);
				$tmp_tab=get_class_from_ele_login($lig_t_incident->login_ele);
				if(isset($tmp_tab['liste_nbsp'])) {
					$texte.=" (<em>".$tmp_tab['liste_nbsp']."</em>)";
				}
				$texte.="</td>\n";
				$cpt_mes=0;
				while($lig_mes_ele=mysqli_fetch_object($res_mes_ele)) {
					if($cpt_mes>0) {$texte.="<tr>\n";}
					$texte.="<td style='font-size:x-small;'>\n";
					$texte.="$lig_mes_ele->mesure";
					$texte.="</td>\n";

					if($cpt_mes==0) {
						$tab_doc_joints=get_documents_joints($id_incident, "mesure", $lig_t_incident->login_ele);
						$chemin="../$dossier_documents_discipline/incident_".$id_incident."/mesures/".$lig_t_incident->login_ele;
						if(count($tab_doc_joints)>0) {
							$texte.="<td rowspan='$nb_mes_ele'>\n";
							for($loop=0;$loop<count($tab_doc_joints);$loop++) {
								$texte.="<a href='$chemin/$tab_doc_joints[$loop]' target='_blank'>$tab_doc_joints[$loop]</a><br />\n";
							}
							$texte.="</td>\n";
						}
					}

					$texte.="</tr>\n";


					// 
					if(mb_strtolower($lig_mes_ele->mesure)=='retenue') {
						$sql="SELECT 1=1 FROM s_retenues sr, s_sanctions s WHERE s.id_sanction=sr.id_sanction AND s.id_incident='$id_incident' AND s.login='$lig_t_incident->login_ele';";
						//$texte.="<tr><td>$sql</td></tr>";
						$test=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($test)==0) {
							$mesure_demandee_non_validee="y";
							//$retenue_demandee_non_validee="y";
						}
					}
					elseif(mb_strtolower($lig_mes_ele->mesure)=='exclusion') {
						$sql="SELECT 1=1 FROM s_exclusions se, s_sanctions s WHERE s.id_sanction=se.id_sanction AND s.id_incident='$id_incident' AND s.login='$lig_t_incident->login_ele';";
						//$texte.="<tr><td>$sql</td></tr>";
						$test=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($test)==0) {
							$mesure_demandee_non_validee="y";
							//$exclusion_demandee_non_validee="y";
						}
					}
					else {
						//$sql="SELECT 1=1 FROM s_types_sanctions sts WHERE sts.nature='".addslashes($lig_mes_ele->mesure)."';";
						$sql="SELECT 1=1 FROM s_types_sanctions2 sts WHERE sts.nature='".addslashes($lig_mes_ele->mesure)."';";
						//$texte.="<tr><td>$sql</td></tr>";
						$test=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($test)>0) {
							// Il existe un nom de sanction correspondant au nom de la mesure demandée.

							//$sql="SELECT 1=1 FROM s_autres_sanctions sa, s_types_sanctions sts, s_sanctions s WHERE s.id_sanction=sa.id_sanction AND sa.id_nature=sts.id_nature AND sts.nature='".addslashes($lig_mes_ele->mesure)."' AND s.id_incident='$id_incident' AND s.login='$lig_t_incident->login_ele';";
							$sql="SELECT 1=1 FROM s_autres_sanctions sa, s_types_sanctions2 sts, s_sanctions s WHERE s.id_sanction=sa.id_sanction AND sa.id_nature=sts.id_nature AND sts.nature='".addslashes($lig_mes_ele->mesure)."' AND s.id_incident='$id_incident' AND s.login='$lig_t_incident->login_ele';";
							//$texte.="<tr><td>$sql</td></tr>";
							$test=mysqli_query($GLOBALS["mysqli"], $sql);
							if(mysqli_num_rows($test)==0) {
								$mesure_demandee_non_validee="y";
							}
						}
					}

					$cpt_mes++;
				}
	
				$cpt_tmp++;
			}
			$texte.="</table>\n";

		//$texte.="</td>\n";
		//$texte.="</tr>\n";
	}

	if((mysqli_num_rows($res_t_incident)>0)||
		(mysqli_num_rows($res_t_incident2)>0)) {
		$texte.="</table>";
	}

	return $texte;
}

function rappel_incident($id_incident, $mode_retour='echo') {
	global $mod_disc_terme_incident;

	$retour="";

	$retour.="<p class='bold'>Rappel de l'".$mod_disc_terme_incident;
	if(isset($id_incident)) {
		$retour.=" n°$id_incident";

		$sql="SELECT declarant FROM s_incidents WHERE id_incident='$id_incident';";
		$res_dec=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_dec)>0) {
			$lig_dec=mysqli_fetch_object($res_dec);
			$retour.=" (<span style='font-size:x-small; font-style:italic;'>signalé par ".u_p_nom($lig_dec->declarant)."</span>)";
		}
	}
	$retour.="&nbsp;:</p>\n";
	$retour.="<blockquote>\n";

	$sql="SELECT * FROM s_incidents WHERE id_incident='$id_incident';";
	//$retour.="$sql<br />\n";
	$res_incident=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res_incident)>0) {
		$lig_incident=mysqli_fetch_object($res_incident);

		$retour.="<table class='boireaus' border='1' summary='".ucfirst($mod_disc_terme_incident)."'>\n";
		$retour.="<tr class='lig1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Date: </td><td style='text-align:left;'>".formate_date($lig_incident->date)."</td></tr>\n";
		$retour.="<tr class='lig-1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Heure: </td><td style='text-align:left;'>$lig_incident->heure</td></tr>\n";

		$retour.="<tr class='lig1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Lieu: </td><td style='text-align:left;'>";
		/*
		$sql="SELECT lieu FROM s_lieux_incidents WHERE id='$lig_incident->id_lieu';";
		$res_lieu_incident=mysql_query($sql);
		if(mysql_num_rows($res_lieu_incident)>0) {
			$lig_lieu_incident=mysql_fetch_object($res_incident);
			$retour.=$lig_lieu_incident->lieu;
		}
		*/
		$retour.=get_lieu_from_id($lig_incident->id_lieu);
		$retour.="</td></tr>\n";

		$retour.="<tr class='lig-1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Nature: </td><td style='text-align:left;'>$lig_incident->nature</td></tr>\n";
		$retour.="<tr class='lig1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Description: </td><td style='text-align:left;'>".nl2br($lig_incident->description)."</td></tr>\n";

		/*
		$sql="SELECT * FROM s_traitement_incident sti, s_mesures s WHERE sti.id_incident='$id_incident' AND sti.id_mesure=s.id;";
		$res_t_incident=mysql_query($sql);
		if(mysql_num_rows($res_t_incident)>0) {
			$retour.="<tr class='lig-1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Mesures&nbsp;: </td>\n";
			$retour.="<td style='text-align:left;'>";
			while($lig_t_incident=mysql_fetch_object($res_t_incident)) {
				$retour.="$lig_t_incident->mesure (<em style='color:green;'>mesure $lig_t_incident->type</em>)<br />";
			}
			$retour.="</td>\n";
			$retour.="</tr>\n";
		}
		*/
		$texte=affiche_mesures_incident($lig_incident->id_incident);
		if($texte!='') {
			$retour.="<tr class='lig-1'><td style='font-weight:bold;vertical-align:top;text-align:left;'>Mesures&nbsp;: </td>\n";
			$retour.="<td style='text-align:left;'>";
			$retour.=$texte;
			$retour.="</td>\n";
			$retour.="</tr>\n";
		}
		$retour.="</table>\n";
	}
	else {
		$retour.="<p>L'".$mod_disc_terme_incident." n°$id_incident ne semble pas enregistré???</p>\n";
	}
	$retour.="</blockquote>\n";

	if($mode_retour=='echo') {
		echo $retour;
	}
	else {
		return $retour;
	}
}

function tab_lignes_adresse($ele_login) {
	global $gepiSchoolPays;

	unset($tab_adr_ligne1);
	unset($tab_adr_ligne2);
	unset($tab_adr_ligne3);

	$sql="SELECT * FROM resp_adr ra, resp_pers rp, responsables2 r, eleves e WHERE e.login='$ele_login' AND r.ele_id=e.ele_id AND r.pers_id=rp.pers_id AND rp.adr_id=ra.adr_id AND (r.resp_legal='1' OR r.resp_legal='2') ORDER BY resp_legal;";
	//echo "$sql<br />";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)==0) {
		return "Aucune adresse de responsable pour cet élève.";
	}
	else {
		$tab_resp=array();
		while($lig=mysqli_fetch_object($res)) {
			$num=$lig->resp_legal-1;

			$tab_resp[$num]=array();

			//$tab_resp[$num]['pers_id']=$lig->pers_id;
			$tab_resp[$num]['nom']=$lig->nom;
			$tab_resp[$num]['prenom']=$lig->prenom;
			$tab_resp[$num]['civilite']=$lig->civilite;

			$tab_resp[$num]['adr_id']=$lig->adr_id;
			$tab_resp[$num]['adr1']=$lig->adr1;
			$tab_resp[$num]['adr2']=$lig->adr2;
			$tab_resp[$num]['adr3']=$lig->adr3;
			$tab_resp[$num]['adr4']=$lig->adr4;
			$tab_resp[$num]['cp']=$lig->cp;
			$tab_resp[$num]['commune']=$lig->commune;
			$tab_resp[$num]['pays']=$lig->pays;

		}

		// Préparation des lignes adresse responsable
		if (!isset($tab_resp[0])) {
			$tab_adr_ligne1[0]="<font color='red'><b>ADRESSE MANQUANTE</b></font>";
			$tab_adr_ligne2[0]="";
			$tab_adr_ligne3[0]="";
		}
		else {
			if (isset($tab_resp[1])) {
				if((isset($tab_resp[1]['adr1']))&&
					(isset($tab_resp[1]['adr2']))&&
					(isset($tab_resp[1]['adr3']))&&
					(isset($tab_resp[1]['adr4']))&&
					(isset($tab_resp[1]['cp']))&&
					(isset($tab_resp[1]['commune']))
				) {
					// Le deuxième responsable existe et est renseigné
					if (($tab_resp[0]['adr_id']==$tab_resp[1]['adr_id']) OR
						(
							($tab_resp[0]['adr1']==$tab_resp[1]['adr1'])&&
							($tab_resp[0]['adr2']==$tab_resp[1]['adr2'])&&
							($tab_resp[0]['adr3']==$tab_resp[1]['adr3'])&&
							($tab_resp[0]['adr4']==$tab_resp[1]['adr4'])&&
							($tab_resp[0]['cp']==$tab_resp[1]['cp'])&&
							($tab_resp[0]['commune']==$tab_resp[1]['commune'])
						)
					) {
						// Les adresses sont identiques
						$nb_bulletins=1;

						if(($tab_resp[0]['nom']!=$tab_resp[1]['nom'])&&
							($tab_resp[1]['nom']!="")) {
							// Les noms des responsables sont différents
							//$tab_adr_ligne1[0]=$tab_resp[0]['civilite']." ".$tab_resp[0]['nom']." ".$tab_resp[0]['prenom']." et ".$tab_resp[1]['civilite']." ".$tab_resp[1]['nom']." ".$tab_resp[1]['prenom'];
							$tab_adr_ligne1[0]=$tab_resp[0]['civilite']." ".$tab_resp[0]['nom']." ".$tab_resp[0]['prenom'];
							//$tab_adr_ligne1[0].=" et ";
							$tab_adr_ligne1[0].="<br />\n";
							$tab_adr_ligne1[0].="et ";
							$tab_adr_ligne1[0].=$tab_resp[1]['civilite']." ".$tab_resp[1]['nom']." ".$tab_resp[1]['prenom'];
						}
						else{
							if(($tab_resp[0]['civilite']!="")&&($tab_resp[1]['civilite']!="")) {
								$tab_adr_ligne1[0]=$tab_resp[0]['civilite']." et ".$tab_resp[1]['civilite']." ".$tab_resp[0]['nom']." ".$tab_resp[0]['prenom'];
							}
							else {
								$tab_adr_ligne1[0]="M. et Mme ".$tab_resp[0]['nom']." ".$tab_resp[0]['prenom'];
							}
						}

						$tab_adr_ligne2[0]=$tab_resp[0]['adr1'];
						if($tab_resp[0]['adr2']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr2'];
						}
						if($tab_resp[0]['adr3']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr3'];
						}
						if($tab_resp[0]['adr4']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr4'];
						}
						$tab_adr_ligne3[0]=$tab_resp[0]['cp']." ".$tab_resp[0]['commune'];

						if(($tab_resp[0]['pays']!="")&&(mb_strtolower($tab_resp[0]['pays'])!=mb_strtolower($gepiSchoolPays))) {
							if($tab_adr_ligne3[0]!=" "){
								$tab_adr_ligne3[0].="<br />";
							}
							$tab_adr_ligne3[0].=$tab_resp[0]['pays'];
						}
					}
					else {
						// Les adresses sont différentes
						//if ($un_seul_bull_par_famille!="oui") {
						// On teste en plus si la deuxième adresse est valide

						if (($tab_resp[1]['adr1']!="")&&
							($tab_resp[1]['commune']!="")
						) {
							$nb_bulletins=2;
						}
						else {
							$nb_bulletins=1;
						}

						for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
							if($tab_resp[$cpt]['civilite']!="") {
								$tab_adr_ligne1[$cpt]=$tab_resp[$cpt]['civilite']." ".$tab_resp[$cpt]['nom']." ".$tab_resp[$cpt]['prenom'];
							}
							else {
								$tab_adr_ligne1[$cpt]=$tab_resp[$cpt]['nom']." ".$tab_resp[$cpt]['prenom'];
							}

							$tab_adr_ligne2[$cpt]=$tab_resp[$cpt]['adr1'];
							if($tab_resp[$cpt]['adr2']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr2'];
							}
							if($tab_resp[$cpt]['adr3']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr3'];
							}
							if($tab_resp[$cpt]['adr4']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr4'];
							}
							$tab_adr_ligne3[$cpt]=$tab_resp[$cpt]['cp']." ".$tab_resp[$cpt]['commune'];

							if(($tab_resp[$cpt]['pays']!="")&&(mb_strtolower($tab_resp[$cpt]['pays'])!=mb_strtolower($gepiSchoolPays))) {
								if($tab_adr_ligne3[$cpt]!=" "){
									$tab_adr_ligne3[$cpt].="<br />";
								}
								$tab_adr_ligne3[$cpt].=$tab_resp[$cpt]['pays'];
							}

						}

					}
				}
				else {
					// Il n'y a pas de deuxième adresse, mais il y aurait un deuxième responsable???
					// CA NE DEVRAIT PAS ARRIVER ETANT DONNé LA REQUETE EFFECTUEE QUI JOINT resp_pers ET resp_adr...
						
						$nb_bulletins=2;

						for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
							if($tab_resp[$cpt]['civilite']!="") {
								$tab_adr_ligne1[$cpt]=$tab_resp[$cpt]['civilite']." ".$tab_resp[$cpt]['nom']." ".$tab_resp[$cpt]['prenom'];
							}
							else {
								$tab_adr_ligne1[$cpt]=$tab_resp[$cpt]['nom']." ".$tab_resp[$cpt]['prenom'];
							}

							$tab_adr_ligne2[$cpt]=$tab_resp[$cpt]['adr1'];
							if($tab_resp[$cpt]['adr2']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr2'];
							}
							if($tab_resp[$cpt]['adr3']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr3'];
							}
							if($tab_resp[$cpt]['adr4']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_resp[$cpt]['adr4'];
							}
							$tab_adr_ligne3[$cpt]=$tab_resp[$cpt]['cp']." ".$tab_resp[$cpt]['commune'];

							if(($tab_resp[$cpt]['pays']!="")&&(mb_strtolower($tab_resp[$cpt]['pays'])!=mb_strtolower($gepiSchoolPays))) {
								if($tab_adr_ligne3[$cpt]!=" "){
									$tab_adr_ligne3[$cpt].="<br />";
								}
								$tab_adr_ligne3[$cpt].=$tab_resp[$cpt]['pays'];
							}
						}
				}
			}
			else {
				// Il n'y a pas de deuxième responsable
				$nb_bulletins=1;

				if($tab_resp[0]['civilite']!="") {
					$tab_adr_ligne1[0]=$tab_resp[0]['civilite']." ".$tab_resp[0]['nom']." ".$tab_resp[0]['prenom'];
				}
				else {
					$tab_adr_ligne1[0]=$tab_resp[0]['nom']." ".$tab_resp[0]['prenom'];
				}

				$tab_adr_ligne2[0]=$tab_resp[0]['adr1'];
				if($tab_resp[0]['adr2']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr2'];
				}
				if($tab_resp[0]['adr3']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr3'];
				}
				if($tab_resp[0]['adr4']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_resp[0]['adr4'];
				}
				$tab_adr_ligne3[0]=$tab_resp[0]['cp']." ".$tab_resp[0]['commune'];

				if(($tab_resp[0]['pays']!="")&&(mb_strtolower($tab_resp[0]['pays'])!=mb_strtolower($gepiSchoolPays))) {
					if($tab_adr_ligne3[0]!=" "){
						$tab_adr_ligne3[0].="<br />";
					}
					$tab_adr_ligne3[0].=$tab_resp[0]['pays'];
				}
			}
		}

		$tab_adresses=array($tab_adr_ligne1,$tab_adr_ligne2,$tab_adr_ligne3);
		return $tab_adresses;
	}
}

function tab_mod_discipline($ele_login,$mode,$date_debut,$date_fin, $restreindre_affichage_a_eleve_seul="n") {
	global $mod_disc_terme_incident, $mod_disc_terme_sanction;
	global $tab_incidents_ele, $tab_mesures_ele, $tab_sanctions_ele;

	$retour="";

	if($date_debut!="") {
		// Tester la validité de la date
		// Si elle n'est pas valide... la vider
		if(preg_match("#/#",$date_debut)) {
			$tmp_tab_date=explode("/",$date_debut);

			if(!checkdate($tmp_tab_date[1],$tmp_tab_date[0],$tmp_tab_date[2])) {
				$date_debut="";
			}
			else {
				$date_debut=$tmp_tab_date[2]."-".$tmp_tab_date[1]."-".$tmp_tab_date[0];
			}
		}
		elseif(preg_match("/-/",$date_debut)) {
			$tmp_tab_date=explode("-",$date_debut);
	
			if(!checkdate($tmp_tab_date[1],$tmp_tab_date[2],$tmp_tab_date[0])) {
				$date_debut="";
			}
		}
		else {
			$date_debut="";
		}
	}

	if($date_fin!="") {
		// Tester la validité de la date
		// Si elle n'est pas valide... la vider
		// Tester la validité de la date
		// Si elle n'est pas valide... la vider
		if(preg_match("#/#",$date_fin)) {
			$tmp_tab_date=explode("/",$date_fin);

			if(!checkdate($tmp_tab_date[1],$tmp_tab_date[0],$tmp_tab_date[2])) {
				$date_fin="";
			}
			else {
				$date_fin=$tmp_tab_date[2]."-".$tmp_tab_date[1]."-".$tmp_tab_date[0];
			}
		}
		elseif(preg_match("/-/",$date_fin)) {
			$tmp_tab_date=explode("-",$date_fin);
	
			if(!checkdate($tmp_tab_date[1],$tmp_tab_date[2],$tmp_tab_date[0])) {
				$date_fin="";
			}
		}
		else {
			$date_fin="";
		}
	}

	$info_dates="";
	$restriction_date="";
	if(($date_debut!="")&&($date_fin!="")) {
		$restriction_date.=" AND (si.date>='$date_debut' AND si.date<='$date_fin') ";
		$info_dates=" (<em>".formate_date($date_debut)."-&gt;".formate_date($date_fin)."</em>)";
	}
	elseif($date_debut!="") {
		$restriction_date.=" AND (si.date>='$date_debut') ";
		$info_dates=" (<em>depuis le ".formate_date($date_debut)."</em>)";
	}
	elseif($date_fin!="") {
		$restriction_date.=" AND (si.date<='$date_fin') ";
		$info_dates=" (<em>jusqu'au ".formate_date($date_fin)."</em>)";
	}

	$tab_incident=array();
	$tab_sanction=array();
	$tab_sanction_non_effectuee=array();
	$tab_mesure=array();
	$zone_de_commentaire = "";
	$sql="SELECT * FROM s_incidents si, s_protagonistes sp WHERE si.id_incident=sp.id_incident AND sp.login='$ele_login' $restriction_date ORDER BY si.date DESC;";
	//$retour.="$sql<br />\n";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		$retour.="<p class='bold'>Tableau des ".$mod_disc_terme_incident."s concernant ".p_nom($ele_login);
		$retour.=$info_dates;
		$retour.="</p>\n";
		$retour.="<table class='boireaus' border='1' summary=\"Tableau des ".$mod_disc_terme_incident."s concernant $ele_login\">\n";
		$retour.="<tr>\n";
		$retour.="<th>Num</th>\n";
		$retour.="<th>Date</th>\n";
		$retour.="<th>Qualité</th>\n";
		$retour.="<th>Description</th>\n";
		$retour.="<th>Suivi</th>\n";
		$retour.="</tr>\n";
		$alt_1=1;
		
		while($lig=mysqli_fetch_object($res)) {
			$alt_1=$alt_1*(-1);
			$retour.="<tr class='lig$alt_1'>\n";

				$retour.="<td>".$lig->id_incident."</td>\n";

				// Modifier l'accès Consultation d'incident... on ne voit actuellement que ses propres incidents
				//$retour.="<td><a href='' target='_blank'>".$lig->id_incident."</a></td>\n";

			$retour.="<td>".formate_date($lig->date);

			$retour.="<br />\n";

			$retour.="<span style='font-size:small;'>".u_p_nom($lig->declarant)."</span>";

			$zone_de_commentaire = $lig->commentaire;

			$retour.="</td>\n";
			$retour.="<td>".$lig->qualite."</td>\n";
			$temoin_eleve_responsable_de_l_incident='n';
			if(mb_strtolower(mb_strtolower($lig->qualite)=='responsable')) {
				if(isset($tab_incident[addslashes($lig->nature)])) {
					$tab_incident[addslashes($lig->nature)]++;
				}
				else {
					$tab_incident[addslashes($lig->nature)]=1;
				}
				$temoin_eleve_responsable_de_l_incident='y';
			}

			$retour.="<td>";
			$retour.="<p style='font-weight: bold;'>".$lig->nature."</p>\n";
			$retour.="<p>".$lig->description."</p>\n";
			
			$retour.="</td>\n";

			$retour.="<td style='padding: 2px;'>";

			$sql="SELECT * FROM s_protagonistes WHERE id_incident='$lig->id_incident' ORDER BY qualite;";
			//echo "$sql<br />\n";
			$res_prot=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res_prot)>0) {
				$retour.="<table class='boireaus' border='1' summary=\"Protagonistes de l ".$mod_disc_terme_incident." n°$lig->id_incident\">\n";

				$alt_2=1;
				while($lig_prot=mysqli_fetch_object($res_prot)) {
					$ligne_visible="y";
					if($lig_prot->statut=='eleve') {
						if($_SESSION['statut']=='eleve') {
							if($_SESSION['login']==$lig_prot->login) {
								$ligne_visible="y";
							}
							else {
								$ligne_visible="n";
							}
						}
						elseif($_SESSION['statut']=='responsable') {
							$ligne_visible="n";
							if(is_responsable($lig_prot->login, $_SESSION['login'])) {
								$ligne_visible="y";
							}
						}
						elseif($restreindre_affichage_a_eleve_seul=="y") {
							$ligne_visible="n";
							if($lig_prot->login==$ele_login) {
								$ligne_visible="y";
							}
						}
					}

					$alt_2=$alt_2*(-1);
					$retour.="<tr class='lig$alt_2'>\n";
					$retour.="<td>";
					if($lig_prot->statut=='eleve') {
						//if((($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable'))&&($ligne_visible!="y")) {
						if($ligne_visible!="y") {
							$retour.="XXX";
						}
						else {
							$retour.=p_nom($lig_prot->login);
						}
					}
					else {
						$retour.=civ_nom_prenom($lig_prot->login,"");
					}
					$retour.="</td>\n";

					$retour.="<td>";
					if($lig_prot->statut=='eleve') {
						//if((($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable'))&&($ligne_visible!="y")) {
						if($ligne_visible!="y") {
							$retour.="XXX";
						}
						else {
							$retour.=$lig_prot->qualite;
						}
					}
					else {
						$retour.=$lig_prot->qualite;
					}
					$retour.="</td>\n";

					$retour.="<td style='padding: 3px;'>\n";
					if($lig_prot->statut=='eleve') {
						//if((($_SESSION['statut']=='eleve')||($_SESSION['statut']=='responsable'))&&($ligne_visible!="y")) {
						if($ligne_visible!="y") {
							$retour.="XXX";
						}
						else {
							$retour.=$lig_prot->qualite;
						}
					}
					else {
						$retour.=$lig_prot->qualite;
					}


					/*
					if((($_SESSION['statut']!='responsable')&&($_SESSION['statut']!='eleve'))||
					($lig_prot->statut!='eleve')||
					(($_SESSION['statut']=='responsable')&&($ligne_visible=="y"))||
					(($_SESSION['statut']=='eleve')&&($ligne_visible=="y"))
					) {
					*/
					if($ligne_visible=="y") {
						$alt=1;
						$sql="SELECT * FROM s_traitement_incident sti, s_mesures sm WHERE sti.id_incident='$lig->id_incident' AND sti.login_ele='$lig_prot->login' AND sm.id=sti.id_mesure ORDER BY mesure;";
						$res_suivi=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($res_suivi)>0) {

							//$retour.="<p style='text-align:left;'>Tableau des mesures pour le protagoniste $lig_prot->login de l incident n°$lig->id_incident</p>\n";
							$retour.="<p style='text-align:left; font-weight: bold;'>Mesures</p>\n";

							$retour.="<table class='boireaus' border='1' summary=\"Tableau des mesures pour le protagoniste $lig_prot->login de l ".$mod_disc_terme_incident." n°$lig->id_incident\">\n";

							$retour.="<tr>\n";
							$retour.="<th>Nature</th>\n";
							$retour.="<th>Mesure</th>\n";
							$retour.="</tr>\n";

							while($lig_suivi=mysqli_fetch_object($res_suivi)) {
								$alt=$alt*(-1);
								$retour.="<tr class='lig$alt'>\n";
								$retour.="<td>$lig_suivi->mesure</td>\n";
							
								if($lig_suivi->type=='prise') {
									$retour.="<td>prise par ".u_p_nom($lig_suivi->login_u)."</td>\n";

									if($temoin_eleve_responsable_de_l_incident=='y') {
										if ($lig_suivi->login_ele==$ele_login) {  //Ajout ERIC test pour ne compter que pour l'élève demandé
											if(isset($tab_mesure[addslashes($lig_suivi->mesure)])) {
											   $tab_mesure[addslashes($lig_suivi->mesure)]++;
											}
											else {
												$tab_mesure[addslashes($lig_suivi->mesure)]=1;
											}
										}
									}
								}
								else {
									$retour.="<td>demandée par ".u_p_nom($lig_suivi->login_u)."</td>\n";
								}
								$retour.="</tr>\n";	
							}	
							$retour.="</table>\n";
						}		
						
						//$sql="SELECT * FROM s_sanctions s WHERE s.id_incident='$lig->id_incident' AND s.login='$lig_prot->login' ORDER BY nature;";
						$sql="SELECT * FROM s_sanctions s, s_types_sanctions2 sts WHERE s.id_incident='$lig->id_incident' AND s.login='$lig_prot->login' AND sts.id_nature=s.id_nature_sanction ORDER BY sts.nature;";
						//$retour.="$sql<br />\n";
						$res_suivi=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($res_suivi)>0) {

							//$retour.="<p style='text-align:left;'>Tableau des sanctions pour le protagoniste $lig_prot->login de l incident n°$lig->id_incident</p>\n";
							$retour.="<p style='text-align:left; font-weight: bold;'>".ucfirst($mod_disc_terme_sanction)."s</p>\n";

							$retour.="<table class='boireaus' border='1' summary=\"Tableau des ".$mod_disc_terme_sanction."s pour le protagoniste $lig_prot->login de l ".$mod_disc_terme_incident." n°$lig->id_incident\">\n";

							$retour.="<tr>\n";
							$retour.="<th>Nature</th>\n";
							$retour.="<th>Date</th>\n";
							$retour.="<th>Description</th>\n";
							$retour.="<th>Effectuée</th>\n";
							$retour.="</tr>\n";
				
						
							while($lig_suivi=mysqli_fetch_object($res_suivi)) {
								$alt=$alt*(-1);
								$retour.="<tr class='lig$alt'>\n";
								$retour.="<td>$lig_suivi->nature</td>\n";
								$retour.="<td>";

								if($temoin_eleve_responsable_de_l_incident=='y') {
									if ($lig_suivi->login==$ele_login) { //Ajout ERIC test pour ne compter que pour l'élève demandé
										if(isset($tab_sanction[addslashes($lig_suivi->nature)])) {
											$tab_sanction[addslashes($lig_suivi->nature)]++;

											if($lig_suivi->effectuee=="O") {
												$tab_sanction_non_effectuee[addslashes($lig_suivi->nature)]+=0;
											}
											else {
												$tab_sanction_non_effectuee[addslashes($lig_suivi->nature)]++;
											}
										}
										else {
											$tab_sanction[addslashes($lig_suivi->nature)]=1;

											if($lig_suivi->effectuee=="O") {
												$tab_sanction_non_effectuee[addslashes($lig_suivi->nature)]=0;
											}
											else {
												$tab_sanction_non_effectuee[addslashes($lig_suivi->nature)]=1;
											}
										}
										// 20160223
										//$retour.="<span style='color:orange;'>\$tab_sanction_non_effectuee[".addslashes($lig_suivi->nature)."]=".$tab_sanction_non_effectuee[addslashes($lig_suivi->nature)]."</span><br />";
									}
								}

								//if($lig_suivi->nature=='retenue') {
								if(mb_strtolower($lig_suivi->type)=='retenue') {
									$sql="SELECT * FROM s_retenues WHERE id_sanction='$lig_suivi->id_sanction';";
									//echo "$sql<br />\n";
									$res_retenue=mysqli_query($GLOBALS["mysqli"], $sql);
									if(mysqli_num_rows($res_retenue)>0) {
										$lig_retenue=mysqli_fetch_object($res_retenue);
										$retour.=formate_date($lig_retenue->date)." (<i>".$lig_retenue->duree."H</i>)";
									}
									else {
										$retour.="X";
									}
								}
								//elseif($lig_suivi->nature=='exclusion') {
								elseif(mb_strtolower($lig_suivi->type)=='exclusion') {
									$sql="SELECT * FROM s_exclusions WHERE id_sanction='$lig_suivi->id_sanction';";
									//echo "$sql<br />\n";
									$res_exclusion=mysqli_query($GLOBALS["mysqli"], $sql);
									if(mysqli_num_rows($res_exclusion)>0) {
										$lig_exclusion=mysqli_fetch_object($res_exclusion);
										$retour.="du ".formate_date($lig_exclusion->date_debut)." (<i>$lig_exclusion->heure_debut</i>) au ".formate_date($lig_exclusion->date_fin)." (<i>$lig_exclusion->heure_fin</i>)<br />$lig_exclusion->lieu";
									}
									else {
										$retour.="X";
									}
								}
								//elseif($lig_suivi->nature=='travail') {
								elseif(mb_strtolower($lig_suivi->type)=='travail') {
									$sql="SELECT * FROM s_travail WHERE id_sanction='$lig_suivi->id_sanction';";
									//echo "$sql<br />\n";
									$res_travail=mysqli_query($GLOBALS["mysqli"], $sql);
									if(mysqli_num_rows($res_travail)>0) {
										$lig_travail=mysqli_fetch_object($res_travail);
										$retour.="pour le ".formate_date($lig_travail->date_retour)."  (<i>$lig_travail->heure_retour</i>)";
									}
									else {
										$retour.="X";
									}
								}

								$retour.="</td>\n";
								$retour.="<td>$lig_suivi->description</td>\n";
								$retour.="<td>$lig_suivi->effectuee</td>\n";
								$retour.="</tr>\n";
							}
						
							$retour.="</table>\n";

						}
					}
					else {
						$retour.="XXX";
					}

					$retour.="</td>\n";

					$retour.="</tr>\n";

				}
				$retour.="</table>\n";

				// Ajout Eric de la zone de commentaire
				//affichage du commentaire
				if((($_SESSION['statut']!='eleve')&&($_SESSION['statut']!='responsable'))||
				(($_SESSION['statut']=='eleve')&&(getSettingAOui('commentaires_mod_disc_visible_eleve')))||
				(($_SESSION['statut']=='responsable')&&(getSettingAOui('commentaires_mod_disc_visible_parent')))) {
					if ($zone_de_commentaire !="") {
						$retour .= "<p style='text-align:left;'><b>Commentaires sur l'".$mod_disc_terme_incident."&nbsp;:&nbsp;</b></br></br>$zone_de_commentaire</p>";
					}
				}
			}

			$retour.="</td>\n";
		}
		$retour.="</table>\n";

		// Totaux
		$retour.="<p style='font-weight: bold;'>Totaux des ".$mod_disc_terme_incident."s/mesures/".$mod_disc_terme_sanction."s en tant que Responsable.</p>\n";

		$retour.="<div style='float:left; width:33%;'>\n";
		$retour.="<p style='font-weight: bold;'>".ucfirst($mod_disc_terme_incident)."s</p>\n";
		if(count($tab_incident)>0) {
			$retour.="<table class='boireaus' border='1' summary='Totaux ".$mod_disc_terme_incident."s'>\n";
			$retour.="<tr><th>Nature</th><th>Total</th></tr>\n";
			$alt=1;
			foreach($tab_incident as $key => $value) {
				$alt=$alt*(-1);
				$retour.="<tr class='lig$alt'><td>".stripslashes($key)."</td><td>".stripslashes($value)."</td></tr>\n";
			}
			$retour.="</table>\n";
		}
		else {
			$retour.="<p>Aucun ".$mod_disc_terme_incident." relevé en qualité de responsable.</p>\n";
		}
		$retour.="</div>\n";

		$retour.="<div style='float:left; width:33%;'>\n";
		if(count($tab_mesure)>0) {
			$retour.="<p style='font-weight: bold;'>Mesures prises</p>\n";
			$retour.="<table class='boireaus' border='1' summary='Totaux mesures prises'>\n";
			$retour.="<tr><th>Mesure</th><th>Total</th></tr>\n";
			$alt=1;
			foreach($tab_mesure as $key => $value) {
				$alt=$alt*(-1);
				$retour.="<tr class='lig$alt'><td>".stripslashes($key)."</td><td>".stripslashes($value)."</td></tr>\n";
			}
			$retour.="</table>\n";
		}
		else {
			$retour.="<p>Aucune mesure prise en qualité de responsable.</p>\n";
		}
		$retour.="</div>\n";

		/*
		// 20160223
		echo "\$tab_sanction<pre>";
		print_r($tab_sanction);
		echo "</pre>";
		echo "\$tab_sanction_non_effectuee<pre>";
		print_r($tab_sanction_non_effectuee);
		echo "</pre>";
		*/
		$retour.="<div style='float:left; width:33%;'>\n";
		$retour.="<p style='font-weight: bold;'>".ucfirst($mod_disc_terme_sanction)."s</p>\n";
		if(count($tab_sanction)>0) {
			$retour.="<table class='boireaus' border='1' summary='Totaux ".$mod_disc_terme_sanction."s'>\n";
			$retour.="<tr><th>Nature</th><th>Total</th><th>Non<br />effectué(e)</th></tr>\n";
			$alt=1;
			foreach($tab_sanction as $key => $value) {
				$alt=$alt*(-1);
				// 20160223
				//$retour.="<tr class='lig$alt'><td>".stripslashes($key)."</td><td>".stripslashes($value)."</td><td>".count($tab_sanction_non_effectuee[$key])."</td></tr>\n";
				$retour.="<tr class='lig$alt'><td>".stripslashes($key)."</td><td>".stripslashes($value)."</td><td>".$tab_sanction_non_effectuee[$key]."</td></tr>\n";
			}
			$retour.="</table>\n";
		}
		else {
			$retour.="<p>Aucun(e) ".$mod_disc_terme_sanction." en qualité de responsable.</p>\n";
		}
		$retour.="</div>\n";

		$retour.="<div style='clear:both;'></div>\n";

	}
	else {
		$retour="<p>Aucun ".$mod_disc_terme_incident." relevé.</p>\n";
	}

	$tab_incidents_ele[$ele_login]=$tab_incident;
	$tab_mesures_ele[$ele_login]=$tab_mesure;
	foreach($tab_sanction as $key => $value) {
		$tab_sanctions_ele[$ele_login][$key]['total']=$value;
		$tab_sanctions_ele[$ele_login][$key]['non_effectuee']=$tab_sanction_non_effectuee[$key];
	}

	return $retour;
}

function get_destinataires_mail_alerte_discipline($tab_id_classe, $nature="", $type="mail") {
	global $tab_param_mail;
	global $id_incident;

	$retour="";

	$id_categorie="";
	if($nature!="") {
		$sql="SELECT sc.id FROM s_natures sn, s_categories sc WHERE sc.id=sn.id_categorie AND sn.nature='".mysqli_real_escape_string($GLOBALS["mysqli"], $nature)."';";
		//echo "$sql<br />";
		$res_cat=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_cat)) {
			$id_categorie=old_mysql_result($res_cat, 0, "id");
		}
	}

	$tab_dest=array();
	if($type=="mail") {
		$temoin=false;
		for($i=0;$i<count($tab_id_classe);$i++) {
			$sql="SELECT * FROM s_alerte_mail WHERE id_classe='".$tab_id_classe[$i]."' AND type='".$type."';";
			//echo "$sql<br />";
			$res=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res)>0) {
				while($lig=mysqli_fetch_object($res)) {
					if($lig->destinataire=='cpe') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u, j_eleves_cpe jecpe, j_eleves_classes jec WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.login=jecpe.e_login AND jecpe.cpe_login=u.login AND u.email!='';";
					}
					elseif($lig->destinataire=='tous_cpe') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u WHERE u.statut='cpe' AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='professeurs') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.login=jeg.login AND jeg.id_groupe=jgp.id_groupe AND jgp.login=u.login AND u.email!='';";
					}
					elseif($lig->destinataire=='pp') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u, j_eleves_professeurs jep, j_eleves_classes jec WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.id_classe=jep.id_classe AND jec.login=jep.login AND jep.professeur=u.login AND u.email!='';";
					}
					elseif($lig->destinataire=='administrateur') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u WHERE u.statut='administrateur' AND u.email!='';";
					}
					elseif($lig->destinataire=='scolarite') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u, j_scol_classes jsc WHERE jsc.id_classe='".$tab_id_classe[$i]."' AND jsc.login=u.login AND u.email!='';";
					}
					elseif($lig->destinataire=='mail') {
						$temoin=true;
						$adresse_sup = $lig->adresse;
					}
					elseif(($lig->destinataire=='')&&($lig->login!='')) {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u WHERE u.login='".$lig->login."' AND u.email!='';";
					}

					//echo "$sql<br />";
					if ($temoin) { //Cas d'une adresse mail autre
						$tab_dest[] = $adresse_sup;
					} else {
						//echo "$sql<br />";
						$res2=mysqli_query($GLOBALS["mysqli"], $sql);
						if(mysqli_num_rows($res2)>0) {
							while($lig2=mysqli_fetch_object($res2)) {
								$ajouter_mail="y";
								if($id_categorie!="") {
									$sql="SELECT * FROM preferences WHERE login='$lig2->login' AND name='mod_discipline_natures_exclues_mail' AND value LIKE '%|$id_categorie|%';";
									//echo "$sql<br />";
									$test_nat=mysqli_query($GLOBALS["mysqli"], $sql);
									if(mysqli_num_rows($test_nat)>0) {
										$ajouter_mail="n";
									}
								}
								else {
									$mod_discipline_natures_non_categorisees_exclues_mail=getPref($_SESSION['login'],'mod_discipline_natures_non_categorisees_exclues_mail',"n");
									if($mod_discipline_natures_non_categorisees_exclues_mail=="y") {
										$ajouter_mail="n";
									}
								}
								//echo "\$ajouter_mail=$ajouter_mail<br />";

								// si prof, il faut vérifier au besoin, si l'élève (un des élèves) est bien dans un des groupes d'enseignement
								if(($lig2->statut ==='professeur') && (getPref($lig2->login, 'limiteAGroupe', "y"))) {
									//global $id_incident;
									$protagonistes = get_protagonistes($id_incident);
									if(!ElvGroupeProf($lig2->login, $protagonistes)) {
										$ajouter_mail="n";
										// echo ('<br />un prof '.$lig2->login.' ne recevra pas un courriel<br />');
									} else {
										// echo ('<br />un prof '.$lig2->login.' va recevoir un courriel<br />');
									}
								}
								//echo "\$ajouter_mail=$ajouter_mail<br />";

								if($ajouter_mail!="n") {
									if(!in_array($lig2->email,$tab_dest)) {
										$tab_dest[]=$lig2->email;
										//$tab_dest[]="$lig2->prenom $lig2->nom <$lig2->email>";
									}
								}
							}
						}
					}
					$temoin=false;
				}
			}
		}

		for($i=0;$i<count($tab_dest);$i++) {
			if($i>0) {$retour.=", ";}
			$retour.=$tab_dest[$i];
			$tab_param_mail['destinataire'][]=$tab_dest[$i];
		}
	}
	elseif($type=="mod_alerte") {
		$retour=array();
		for($i=0;$i<count($tab_id_classe);$i++) {
			$sql="SELECT * FROM s_alerte_mail WHERE id_classe='".$tab_id_classe[$i]."' AND type='".$type."';";
			//echo "$sql<br />";
			$res=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res)>0) {
				while($lig=mysqli_fetch_object($res)) {
					if($lig->destinataire=='cpe') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u, j_eleves_cpe jecpe, j_eleves_classes jec WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.login=jecpe.e_login AND jecpe.cpe_login=u.login AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='tous_cpe') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u WHERE u.statut='cpe' AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='professeurs') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u, j_eleves_classes jec, j_eleves_groupes jeg, j_groupes_professeurs jgp WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.login=jeg.login AND jeg.id_groupe=jgp.id_groupe AND jgp.login=u.login AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='pp') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u, j_eleves_professeurs jep, j_eleves_classes jec WHERE jec.id_classe='".$tab_id_classe[$i]."' AND jec.id_classe=jep.id_classe AND jec.login=jep.login AND jep.professeur=u.login AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='administrateur') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u WHERE u.statut='administrateur' AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif($lig->destinataire=='scolarite') {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login FROM utilisateurs u, j_scol_classes jsc WHERE jsc.id_classe='".$tab_id_classe[$i]."' AND jsc.login=u.login AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					elseif(($lig->destinataire=='')&&($lig->login!='')) {
						$sql="SELECT DISTINCT u.nom,u.prenom,u.email,u.login,u.statut FROM utilisateurs u WHERE u.login='".$lig->login."' AND u.email!='' AND u.login NOT IN (SELECT value FROM mod_alerte_divers WHERE name='login_exclus');";
					}
					//echo "$sql<br />";
					$res2=mysqli_query($GLOBALS["mysqli"], $sql);
					if(mysqli_num_rows($res2)>0) {
						while($lig2=mysqli_fetch_object($res2)) {
							$ajouter_login="y";
							if($id_categorie!="") {
								$sql="SELECT * FROM preferences WHERE login='$lig2->login' AND name='mod_discipline_natures_exclues_mod_alerte' AND value LIKE '%|$id_categorie|%';";
								//echo "$sql<br />";
								$test_nat=mysqli_query($GLOBALS["mysqli"], $sql);
								if(mysqli_num_rows($test_nat)>0) {
									$ajouter_login="n";
								}
							}
							else {
								$mod_discipline_natures_non_categorisees_exclues_mod_alerte=getPref($_SESSION['login'],'mod_discipline_natures_non_categorisees_exclues_mod_alerte',"n");
								if($mod_discipline_natures_non_categorisees_exclues_mod_alerte=="y") {
									$ajouter_login="n";
								}
							}
							//echo "\$ajouter_login=$ajouter_login<br />";

							if($ajouter_login!="n") {
								if(!in_array($lig2->login,$tab_dest)) {
									$tab_dest[]=$lig2->login;
								}
							}
						}
					}
				}
			}
		}

		$retour=$tab_dest;
	}

	return $retour;
}

// Retourne à partir de l'id d'un incident le login du déclarant
function get_login_declarant_incident($id_incident) {
	global $mod_disc_terme_incident;

	$retour="";
	//$sql_declarant="SELECT DISTINCT SI.id_incident, SI.declarant FROM s_incidents SI, s_sanctions SS WHERE SI.id_incident='$id_incident' AND SI.id_incident=SS.id_incident;";
	$sql_declarant="SELECT DISTINCT SI.id_incident, SI.declarant FROM s_incidents SI WHERE SI.id_incident='$id_incident';";
	//echo $sql_declarant;
	$res_declarant=mysqli_query($GLOBALS["mysqli"], $sql_declarant);
	if(mysqli_num_rows($res_declarant)>0) {
		$lig_declarant=mysqli_fetch_object($res_declarant);
		$retour= $lig_declarant->declarant;	
	} else {
		$retour=ucfirst($mod_disc_terme_incident).' inconnu';
	}
	return $retour;
}

//Fonction dressant la liste des reports pour une sanction ($id_type_sanction)
function afficher_tableau_des_reports($id_sanction) {
	global $mod_disc_terme_sanction;
	global $id_incident;
	$retour="";
	$sql="SELECT * FROM s_reports WHERE id_sanction='$id_sanction' ORDER BY id_report;";
	//echo $sql;
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		echo "<table class='boireaus' border='1' summary='Liste des reports' style='margin:2px;'>\n";
		echo "<tr>\n";
		echo "<th>Report N°</th>\n";
		echo "<th>Date</th>\n";
		echo "<th>Information</th>\n";
		echo "<th>motif</th>\n";
		echo "<th>Suppr</th>\n";
		echo "</tr>\n";
		$alt_b=1;
		$cpt=1;
		while($lig=mysqli_fetch_object($res)) {
			$alt_b=$alt_b*(-1);
			echo "<tr class='lig$alt_b'>\n";
			echo "<td>".$cpt."</td>\n";
			$tab_date=explode("-",$lig->date);
			echo "<td>".$tab_date[2]."-".sprintf("%02d",$tab_date[1])."-".sprintf("%02d",$tab_date[0])."</td>\n";
			echo "<td>".$lig->informations."</td>\n";
			echo "<td>".$lig->motif_report."</td>\n";
			echo "<td><a href='".$_SERVER['PHP_SELF']."?mode=suppr_report&amp;id_report=$lig->id_report&amp;id_sanction=$lig->id_sanction&amp;id_incident=$id_incident&amp;".add_token_in_url()."' title='Supprimer le report n°$lig->id_report'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer le report n°$lig->id_report' /></a></td>\n";

			echo "<tr/>";
			$cpt++;
		}
		echo "</table>\n";
	} else {
		$retour = "Aucun report actuellement pour cette ".$mod_disc_terme_sanction.".";
	}	
	return $retour;
}

//Fonction donnant le nombre de reports pour une sanction ($id_type_sanction)
function nombre_reports($id_sanction,$aucun) {
	$sql="SELECT * FROM s_reports WHERE id_sanction='$id_sanction' ORDER BY id_report;";
	//echo $sql;
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		$cpt=0;
		while($lig=mysqli_fetch_object($res)) {
			$cpt++;
		}
	} else {
		$cpt = $aucun;
	}	
	return $cpt;
}

// Retourne à partir de l'id d'un incident le login du déclarant
function get_protagonistes($id_incident,$roles=array(),$statuts=array()) {
	$retour=array();

	$chaine_roles="";
	if(count($roles)>0) {
		$chaine_roles=" AND (";
		for($loop=0;$loop<count($roles);$loop++) {
			if($loop>0) {$chaine_roles.=" OR ";}
			$chaine_roles.="qualite='$roles[$loop]'";
		}
		$chaine_roles.=")";
	}

	$chaine_statuts="";
	if(count($statuts)>0) {
		$chaine_statuts=" AND (";
		for($loop=0;$loop<count($statuts);$loop++) {
			if($loop>0) {$chaine_statuts.=" OR ";}
			$chaine_statuts.="statut='$statuts[$loop]'";
		}
		$chaine_statuts.=")";
	}

	$sql="SELECT * FROM s_protagonistes WHERE id_incident='$id_incident' $chaine_roles $chaine_statuts ORDER BY qualite, login;";
	//echo "$sql<br />";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		while($lig=mysqli_fetch_object($res)) {
		  $retour[]=$lig->login;
		}
	}
	return $retour;
}

function ElvGroupeProf ($profLogin, $protagonistes) {
	global $mysqli;
	foreach ($protagonistes as $protagoniste) {
		$sql="SELECT e.* FROM j_eleves_groupes e "
		   . "INNER JOIN j_groupes_professeurs p "
		   . "ON e.id_groupe = p.id_groupe "
		   . "WHERE e.login = '$protagoniste' "
		   . "AND p.login = '$profLogin' ";
		// echo "$sql<br />";
		$res=mysqli_query($mysqli, $sql);
		if ($res->num_rows) {
			return TRUE;
		}
	}
	return FALSE;
}

function get_documents_joints($id, $type, $login_ele="") {
	// $type: mesure ou sanction
	// $login_ele doit être non vide pour les mesures
	global $dossier_documents_discipline;
	$tab_file=array();

	$id_incident="";

	if(($type=="mesure")||($type=="sanction")) {
		if($type=="mesure") {
			$id_incident=$id;

			$path="../$dossier_documents_discipline/incident_".$id_incident."/mesures/$login_ele";
		}
		else {
			$sql="SELECT id_incident FROM s_sanctions WHERE id_sanction='$id';";
			$res=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res)>0) {
				$lig=mysqli_fetch_object($res);
				$id_incident=$lig->id_incident;

				$path="../$dossier_documents_discipline/incident_".$id_incident."/sanction_".$id;
			}
		}

		if(isset($path)) {
			$tab_file=get_file_in_dir($path);
		}
	}

	return $tab_file;
}

function get_file_in_dir($path) {
	$tab_file=array();

	if(file_exists($path)) {
		$handle=opendir($path);
		$n=0;
		while ($file = readdir($handle)) {
			if (($file != '.') and ($file != '..') and ($file != 'remove.txt')
			and ($file != '.htaccess') and ($file != '.htpasswd') and ($file != 'index.html')) {
				$tab_file[] = $file;
			}
		}
		closedir($handle);
		sort($tab_file);
	}

	return $tab_file;
}

function sanction_documents_joints($id_incident, $ele_login) {
	global $id_sanction;
	global $dossier_documents_discipline;

	$retour="";
	if((isset($id_sanction))&&($id_sanction!='')) {
		$tab_doc_joints=get_documents_joints($id_sanction, "sanction", $ele_login);
		if(count($tab_doc_joints)>0) {
			$chemin="../$dossier_documents_discipline/incident_".$id_incident."/sanction_".$id_sanction;

			$retour.="<table class='boireaus' width='100%'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Fichiers joints</th>\n";
			$retour.="<th>Supprimer</th>\n";
			$retour.="</tr>\n";
			$alt3=1;
			for($loop=0;$loop<count($tab_doc_joints);$loop++) {
				$alt3=$alt3*(-1);
				$retour.="<tr class='lig$alt3 white_hover'>\n";
				$retour.="<td><a href='$chemin/$tab_doc_joints[$loop]' target='_blank'>$tab_doc_joints[$loop]</a></td>\n";
				$retour.="<td><input type='checkbox' name='suppr_doc_joint[]' value=\"$tab_doc_joints[$loop]\" /></td>\n";
				// PB: Est-ce qu'on ne risque pas de permettre d'aller supprimer des fichiers d'un autre incident?
				//     Tester le nom de fichier et l'id_incident
				//     Fichier en ../$dossier_documents_discipline/incident_<$id_incident>/mesures/<LOGIN_ELE>
				$retour.="</tr>\n";
			}
			$retour.="</table>\n";
		}
	}

	$retour.="<p>Joindre un fichier&nbsp;: <input type=\"file\" size=\"15\" name=\"document_joint\" id=\"document_joint\" /><br />\n";


	$tab_doc_joints2=get_documents_joints($id_incident, "mesure", $ele_login);
	if(count($tab_doc_joints2)>0) {
		$temoin_deja_tous_joints="n";
		if(isset($tab_doc_joints)) {
			$temoin_deja_tous_joints="y";
			for($loop=0;$loop<count($tab_doc_joints2);$loop++) {
				if(!in_array($tab_doc_joints2[$loop], $tab_doc_joints)) {
					$temoin_deja_tous_joints="n";
					break;
				}
			}
		}

		if($temoin_deja_tous_joints=="n") {
			//$retour.="Joindre&nbsp;:<br />\n";
			$chemin="../$dossier_documents_discipline/incident_".$id_incident."/mesures/".$ele_login;
	
			$retour.="<b>Fichiers proposés lors de la saisie des mesures demandées&nbsp;:</b>";
			$retour.="<table class='boireaus' width='100%'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Joindre</th>\n";
			$retour.="<th>Fichier</th>\n";
			$retour.="</tr>\n";
			$alt3=1;
			for($loop=0;$loop<count($tab_doc_joints2);$loop++) {
				if((!isset($tab_doc_joints))||(!in_array($tab_doc_joints2[$loop],$tab_doc_joints))) {
					$alt3=$alt3*(-1);
					$retour.="<tr class='lig$alt3 white_hover'>\n";
					$retour.="<td><input type='checkbox' name='ajouter_doc_joint[]' value=\"$tab_doc_joints2[$loop]\" ";
					//if((!isset($tab_doc_joints))||(!in_array($tab_doc_joints2[$loop],$tab_doc_joints))) {
						$retour.="checked ";
					//}
					$retour.="/>\n";
					$retour.="</td>\n";
					$retour.="<td><a href='$chemin/$tab_doc_joints2[$loop]' target='_blank'>$tab_doc_joints2[$loop]</a></td>\n";
					$retour.="</tr>\n";
				}
			}
			$retour.="</table>\n";
		}
	}

	return $retour;
}

function liste_doc_joints_sanction($id_sanction, $mode="html") {
	global $dossier_documents_discipline;
	$retour="";

	$tab_doc_joints=get_documents_joints($id_sanction, "sanction");
	if(count($tab_doc_joints)>0) {
		$sql="SELECT id_incident FROM s_sanctions WHERE id_sanction='$id_sanction';";
		$res=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res)>0) {
			$lig=mysqli_fetch_object($res);
			$id_incident=$lig->id_incident;

			$chemin="../$dossier_documents_discipline/incident_".$id_incident."/sanction_".$id_sanction;
	
			for($loop=0;$loop<count($tab_doc_joints);$loop++) {
				if($mode=="html") {
					$retour.="<a href='$chemin/$tab_doc_joints[$loop]' target='_blank'>$tab_doc_joints[$loop]</a><br />\n";
				}
				elseif($mode="liste_en_ligne") {
					$retour.="$tab_doc_joints[$loop], ";
				}
				else {
					if($loop>0) {
						$retour.=",\n";
					}
					$retour.="$tab_doc_joints[$loop]";
				}
			}
		}
	}

	return $retour;
}

function suppr_doc_joints_incident($id_incident, $suppr_doc_sanction='n') {
	global $dossier_documents_discipline;
	$retour="";

	$sql="SELECT login FROM s_protagonistes WHERE id_incident='$id_incident';";
	//echo "$sql<br />";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		$temoin_erreur="n";

		while($lig=mysqli_fetch_object($res)) {
			//echo "\$lig->login=$lig->login<br />";
			$tab_doc_joints=get_documents_joints($id_incident, "mesure", $lig->login);
			//echo "count(\$tab_doc_joints)=".count($tab_doc_joints)."<br />";
			if(count($tab_doc_joints)>0) {
				$chemin="../$dossier_documents_discipline/incident_".$id_incident."/mesures/".$lig->login;
				//echo "$chemin<br />";
				$temoin_erreur="n";
				for($loop=0;$loop<count($tab_doc_joints);$loop++) {
					if(!unlink($chemin."/".$tab_doc_joints[$loop])) {
						$retour.="Erreur lors de la suppression de $chemin/$tab_doc_joints[$loop]<br />";
						$temoin_erreur="y";
					}
				}
				if($temoin_erreur=="n") {
					rmdir($chemin);
				}
			}
		}

		if($temoin_erreur=="n") {
			if($suppr_doc_sanction=='y') {
				$sql="SELECT id_sanction FROM s_sanctions WHERE id_incident='$id_incident';";
				$res=mysqli_query($GLOBALS["mysqli"], $sql);
				if(mysqli_num_rows($res)>0) {
					while($lig=mysqli_fetch_object($res)) {
						$retour.=suppr_doc_joints_sanction($lig->id_sanction);
					}
				}
			}

			if((file_exists("../$dossier_documents_discipline/incident_".$id_incident."/mesures"))&&(rmdir("../$dossier_documents_discipline/incident_".$id_incident."/mesures"))) {
				if(file_exists("../$dossier_documents_discipline/incident_".$id_incident)) {rmdir("../$dossier_documents_discipline/incident_".$id_incident);}
			}
		}
	}

	return $retour;
}

function suppr_doc_joints_sanction($id_sanction) {
	global $dossier_documents_discipline;

	$retour="";

	$sql="SELECT id_incident FROM s_sanctions WHERE id_sanction='$id_sanction';";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		$lig=mysqli_fetch_object($res);
		$id_incident=$lig->id_incident;

		$tab_doc_joints=get_documents_joints($id_sanction, "sanction");
		if(count($tab_doc_joints)>0) {
			$chemin="../$dossier_documents_discipline/incident_".$id_incident."/sanction_".$id_sanction;
			for($loop=0;$loop<count($tab_doc_joints);$loop++) {
				if(!unlink($chemin."/".$tab_doc_joints[$loop])) {
					$retour.="Erreur lors de la suppression de $chemin/$tab_doc_joints[$loop]<br />";
				}
			}
			rmdir($chemin);
		}
	}

	return $retour;
}

function lien_envoi_mail_rappel($id_sanction, $num, $id_incident="") {
	global $mod_disc_terme_incident;
	global $mod_disc_terme_sanction;

	$retour="";

	if(($id_sanction!="")||($id_incident!="")) {
		$trame_message="Bonjour, \n";

		if($id_sanction=="") {
			$login_declarant=get_login_declarant_incident($id_incident);

			//pour le mail
			$mail_declarant = retourne_email($login_declarant);
			//echo add_token_field(true);
			$retour.="<input type='hidden' name='sujet_mail_rappel_$num' id='sujet_mail_rappel_$num' value=\"[GEPI] Discipline : Demande de travail pour une ".$mod_disc_terme_sanction."\" />\n";
			$retour.="<input type='hidden' name='destinataire_mail_rappel_$num' id='destinataire_mail_rappel_$num' value=\"".$mail_declarant."\" />\n";

			$num_incident=$id_incident;

			$chaine_protagonistes="";
			$tab_protagonistes=get_protagonistes($id_incident, array('Responsable'), array('eleve'));
			for($loop=0;$loop<count($tab_protagonistes);$loop++) {
				if($loop>0) {$chaine_protagonistes.=", ";}
				$chaine_protagonistes.=get_nom_prenom_eleve($tab_protagonistes[$loop],'avec_classe');
			}

			//$trame_message.="La sanction (voir l'incident N°%num_incident%) de %prenom_nom% (%classe%) est planifiée.\n";
			$trame_message.="La ".$mod_disc_terme_sanction." (voir l'".$mod_disc_terme_incident." N°$num_incident) de $chaine_protagonistes est planifiée.\n";
		}
		else {
			$sql="SELECT * FROM s_sanctions WHERE id_sanction='$id_sanction';";
			$res=mysqli_query($GLOBALS["mysqli"], $sql);
			if(mysqli_num_rows($res)>0) {
				$lig_sanction=mysqli_fetch_object($res);
	
				$login_declarant=get_login_declarant_incident($lig_sanction->id_incident);
			
				//pour le mail
				$mail_declarant = retourne_email($login_declarant);
				//echo add_token_field(true);
				$retour.="<input type='hidden' name='sujet_mail_rappel_$num' id='sujet_mail_rappel_$num' value=\"[GEPI] Discipline : Demande de travail pour une $lig_sanction->nature\" />\n";
				$retour.="<input type='hidden' name='destinataire_mail_rappel_$num' id='destinataire_mail_rappel_$num' value=\"".$mail_declarant."\" />\n";

				$num_incident=$lig_sanction->id_incident;
				$prenom_nom=p_nom($lig_sanction->login) ;
				$tmp_tab=get_class_from_ele_login($lig_sanction->login);
				if(isset($tmp_tab['liste_nbsp'])) {$classe= $tmp_tab['liste_nbsp'];}
		
				if($lig_sanction->nature="retenue") {
					//$trame_message.="La $lig_sanction->nature (voir l'incident N°%num_incident%) de %prenom_nom% (%classe%) est planifiée le %jour% en/à %heure% pour une durée de %duree%H \n";
					$trame_message.="La retenue (voir l'".$mod_disc_terme_incident." N°%num_incident%) de %prenom_nom% (%classe%) est planifiée le %jour% en/à %heure% pour une durée de %duree%H \n";
		
					$sql="SELECT * FROM s_retenues WHERE id_sanction='$lig_sanction->id_sanction';";
					$res2=mysqli_query($GLOBALS["mysqli"], $sql);
					if(mysqli_num_rows($res2)>0) {
						$lig_retenue=mysqli_fetch_object($res2);
					
						$date=formate_date($lig_retenue->date);
						$heure=$lig_retenue->heure_debut;
						$duree=$lig_retenue->duree;
		
						$trame_message=str_replace("%jour%",$date,$trame_message);
						$trame_message=str_replace("%heure%",$heure,$trame_message);
						$trame_message=str_replace("%duree%",$duree,$trame_message);
					}
				}
				elseif($lig_sanction->nature="exclusion") {
					$trame_message.="L'exclusion (voir l'".$mod_disc_terme_incident." N°%num_incident%) de %prenom_nom% (%classe%) est planifiée du %jour_debut% au %jour_fin% \n";
		
					$sql="SELECT * FROM s_exclusions WHERE id_sanction='$lig_sanction->id_sanction';";
					$res2=mysqli_query($GLOBALS["mysqli"], $sql);
					if(mysqli_num_rows($res2)>0) {
						$lig_exclusion=mysqli_fetch_object($res2);
					
						$date_debut=formate_date($lig_exclusion->date_debut);
						$date_fin=formate_date($lig_exclusion->date_fin);
		
						$trame_message=str_replace("%jour_debut%",$date_debut,$trame_message);
						$trame_message=str_replace("%jour_fin%",$date_fin,$trame_message);
					}
				}
				elseif($lig_sanction->nature="travail") {
					$trame_message.="Le travail (voir l'".$mod_disc_terme_incident." N°%num_incident%) de %prenom_nom% (%classe%) est planifié pour une date de retour au %jour_retour% à %heure_retour% \n";
		
					$sql="SELECT * FROM s_travail WHERE id_sanction='$lig_sanction->id_sanction';";
					$res2=mysqli_query($GLOBALS["mysqli"], $sql);
					if(mysqli_num_rows($res2)>0) {
						$lig_travail=mysqli_fetch_object($res2);
					
						$date_retour=formate_date($lig_travail->date_retour);
						$heure_retour=formate_date($lig_travail->heure_retour);
		
						$trame_message=str_replace("%jour_retour%",$date_retour,$trame_message);
						$trame_message=str_replace("%heure_retour%",$heure_retour,$trame_message);
					}
				}
				else {
					$trame_message.="La ".$mod_disc_terme_sanction." '$lig_sanction->nature' (voir l'".$mod_disc_terme_incident." N°%num_incident%) de %prenom_nom% (%classe%) est planifiée.\n";
				}
			}

			$trame_message=str_replace("%num_incident%",$num_incident,$trame_message);
			$trame_message=str_replace("%prenom_nom%",$prenom_nom,$trame_message);
			$trame_message=str_replace("%classe%",$classe,$trame_message);

		}
	
		//echo "<td>\n";	
		$ligne_nom_declarant=u_p_nom($login_declarant);
		$retour.="$ligne_nom_declarant";

		$trame_message.="Merci d'apporter le travail prévu à la vie scolaire.\n\n-- \nLa vie scolaire";

		//echo $trame_message;
		$retour.="<input type='hidden' name='message_mail_rappel_$num' id='message_mail_rappel_$num' value=\"$trame_message\"/>\n";

		//on autorise l'envoi de mail que pour les statuts Admin / CPE / Scolarite
		if(($_SESSION['statut']=='administrateur') || ($_SESSION['statut']=='cpe') || ($_SESSION['statut']=='scolarite')) {
			//if($lig_sanction->effectuee!="O") {
			if((!isset($lig_sanction))||($lig_sanction->effectuee!="O")) {
				$retour.="<span id='mail_envoye_$num'><a href='#' onclick=\"envoi_mail_rappel_sanction($num);return false;\"><img src='../images/icons/icone_mail.png' width='25' height='25' alt='Envoyer un mail pour demander le travail au déclarant' title='Envoyer un mail pour demander le travail au déclarant' /></a></span>";
			}
		}
	}
	return $retour;
}

function envoi_mail_rappel_js() {
	$retour="<script type='text/javascript'>
	// <![CDATA[
	function envoi_mail_rappel_sanction(num) {
		csrf_alea=document.getElementById('csrf_alea').value;
		destinataire=document.getElementById('destinataire_mail_rappel_'+num).value;
		sujet_mail=document.getElementById('sujet_mail_rappel_'+num).value;
		message=document.getElementById('message_mail_rappel_'+num).value;
		new Ajax.Updater($('mail_envoye_'+num),'../bulletin/envoi_mail.php?destinataire='+destinataire+'&sujet_mail='+sujet_mail+'&message='+escape(message)+'&csrf_alea='+csrf_alea,{method: 'get'});
	}
	//]]>
</script>\n";
	return $retour;
}

function get_nature_sanction($id_nature_sanction) {
	$retour="";
	$sql="SELECT nature FROM s_types_sanctions2 WHERE id_nature='$id_nature_sanction';";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		$retour=old_mysql_result($res,0,"nature");
	}
	return $retour;
}

function sanction_saisie_par($id_sanction, $login) {
	$sql="SELECT 1=1 FROM s_sanctions WHERE id_sanction='$id_sanction' AND saisie_par='".$login."';";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)==0) {
		return false;
	}
	else {
		return true;
	}
}

function liste_sanctions($id_incident, $ele_login) {
	global $mod_disc_terme_incident;
	global $mod_disc_terme_sanction;
	global $gepiPath;

	// Pour que les infobulles définies ici fonctionnent même si elles sont appelées depuis une autre infobulle
	global $tabdiv_infobulle;
	global $delais_affichage_infobulle,$largeur_survol_infobulle,$hauteur_survol_infobulle;

	$retour="";

	$sql="SELECT etat, declarant FROM s_incidents WHERE id_incident='$id_incident';";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)==0) {
		$retour="<p style='color:red;'>L'incident n°$id_incident n'existe pas???</p>\n";
	}
	else {
		$lig_inc=mysqli_fetch_object($res);
		$etat_incident=$lig_inc->etat;
		$declarant=$lig_inc->declarant;

		// Retenues
		$sql="SELECT * FROM s_sanctions s, s_retenues sr WHERE s.id_incident=$id_incident AND s.login='".$ele_login."' AND sr.id_sanction=s.id_sanction ORDER BY sr.date, sr.heure_debut;";
		//$retour.="$sql<br />\n";
		$res_sanction=mysqli_query($GLOBALS["mysqli"], $sql);
		$res_sanction_tmp=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_sanction)>0) {
			$retour.="<table class='boireaus' border='1' summary='Retenues' style='margin:2px;'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Nature</th>\n";
			$retour.="<th>Date</th>\n";
			$retour.="<th>Heure</th>\n";
			$retour.="<th>Durée</th>\n";
			$retour.="<th>Lieu</th>\n";
			$retour.="<th>Travail</th>\n";
			
			$lig_sanction_tmp=mysqli_fetch_object($res_sanction_tmp);
			$nombre_de_report=nombre_reports($lig_sanction_tmp->id_sanction,0);
			if ($nombre_de_report <> 0) {
			   $retour.="<th>Nbre report</th>\n";
			}

			// 20160315
			if(getSettingAOui("active_mod_ooo")) {
				$retour.="<th>Imprimer</th>\n";
			}

			// 20141106
			//if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Effectuée</th>\n";
			//}

			//if($etat_incident!='clos') {
			if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Suppr</th>\n";
			}
			$retour.="</tr>\n";
			$alt_b=1;
			while($lig_sanction=mysqli_fetch_object($res_sanction)) {
				$alt_b=$alt_b*(-1);
				$retour.="<tr class='lig$alt_b'>\n";
				//$retour.="<td>Retenue</td>\n";
				if(($etat_incident!='clos')&&(($_SESSION['statut']!='professeur')&&($_SESSION['statut']!='autre'))) {
					$retour.="<td><a href='saisie_sanction.php?mode=modif&amp;valeur=$lig_sanction->id_nature_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident&amp;ele_login=$ele_login'>".ucfirst($lig_sanction->nature)."</a></td>\n";
				}
				else {
					$retour.="<td>".ucfirst($lig_sanction->nature)."</td>\n";
				}
				$retour.="<td>".formate_date($lig_sanction->date)."</td>\n";
				$retour.="<td>$lig_sanction->heure_debut</td>\n";
				$retour.="<td>$lig_sanction->duree</td>\n";
				$retour.="<td>$lig_sanction->lieu</td>\n";
				//$retour.="<td>".nl2br($lig_sanction->travail)."</td>\n";
				
				$retour.="<td>";

				// 20160108
				$texte="";
				if(($etat_incident!='clos')&&($_SESSION['login']==$declarant)) {
					/*
					$texte_ajout_travail="<form action='".$_SERVER['PHP_SELF']."' method='post' enctype='multipart/form-data'>
	".add_token_field()."
	<input type='hidden' name='valider_saisie_travail' value='y' />
	<input type='hidden' name='id_incident' value='".$lig_sanction->id_incident."' />
	<input type='hidden' name='id_sanction' value='".$lig_sanction->id_sanction."' />
	<input type='hidden' name='ele_login' value='".$ele_login."' />
	<p><textarea name='no_anti_inject_travail' id='textarea_nature_travail' cols='30' onchange='changement();'>".$lig_sanction->travail."</textarea></p>
	".sanction_documents_joints($lig_sanction->id_incident, $ele_login)."
	<p><input type='submit' value='Valider' /></p>
</form>";
					$tabdiv_infobulle[]=creer_div_infobulle("div_ajout_travail_sanction_".$lig_sanction->id_sanction,"Ajouter travail (".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction)","",$texte_ajout_travail,"",50,0,'y','y','n','n',3);

					$texte.="<div style='float:right; width:16px;'>
	<a href='#' onclick=\"afficher_div('div_ajout_travail_sanction_".$lig_sanction->id_sanction."', 'y', 10, -40);return false;\" title=\"Ajouter du travail ou des documents.\"><img src='../images/icons/add.png' class='icone16' alt='+' /></a>
</div>";
					*/

					$texte.="<div style='float:right; width:16px;'>
	<a href=\"ajout_travail_sanction.php?id_sanction=".$lig_sanction->id_sanction."\" title=\"Ajouter du travail ou des documents.\"><img src='$gepiPath/images/icons/add.png' class='icone16' alt='+' /></a>
</div>";
				}

				$tmp_doc_joints=liste_doc_joints_sanction($lig_sanction->id_sanction);
				if(($lig_sanction->travail=="")&&($tmp_doc_joints=="")) {
					$texte.="Aucun travail";
				}
				else {
					$texte_br=nl2br($lig_sanction->travail);
					$texte.=$texte_br;
					if($tmp_doc_joints!="") {
						if($texte_br!="") {$texte.="<br />";}
						$texte.=$tmp_doc_joints;
					}
				}

				$tabdiv_infobulle[]=creer_div_infobulle("div_travail_sanction_$lig_sanction->id_sanction","Travail (".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction)","",$texte,"",20,0,'y','y','n','n',2);

				$retour.=" <a href='#' onmouseover=\"document.getElementById('div_travail_sanction_$lig_sanction->id_sanction').style.zIndex=document.getElementById('sanctions_incident_$id_incident').style.zIndex+1;delais_afficher_div('div_travail_sanction_$lig_sanction->id_sanction','y',10,-40,$delais_affichage_infobulle,$largeur_survol_infobulle,$hauteur_survol_infobulle);\" onclick=\"return false;\">Détails</a>";
				$retour.="</td>\n";
				
				if ($nombre_de_report <> 0) {
					$retour.="<td>\n";
					$retour.=$nombre_de_report;
					$retour.="</td>";
				}

				// 20160315
				if(getSettingAOui("active_mod_ooo")) {
					$retour.="<td><a href='$gepiPath/mod_discipline/saisie_sanction.php?odt=retenue&id_sanction=".$lig_sanction->id_sanction."&id_incident=$id_incident&ele_login=".$ele_login.add_token_in_url()."' target='_blank'><img src='$gepiPath/images/icons/print.png' class='icone16' alt='Imprimer' /></a></td>\n";
				}

				// 20141106
				// Sanction effectuée
				if($etat_incident=='clos') {
					$retour.="<td>";
					if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					$retour.="</td>\n";
				}
				else {
					$retour.="<td";
					if((in_array($_SESSION['statut'], array('administrateur', 'scolarite', 'cpe')))||
					(($_SESSION['statut']=='professeur')&&(sanction_saisie_par($lig_sanction->id_sanction, $_SESSION['login'])))) {
						$retour.=" title=\"Cliquez pour marquer la sanction comme effectuée ou non effectuée\"";
						if($lig_sanction->effectuee=="O") {
							$valeur_alt="N";
						}
						else {
							$valeur_alt="O";
						}

						$retour.="<a href='#' onclick=\"maj_etat_sanction_effectuee_ou_non($lig_sanction->id_sanction, '$valeur_alt')\">";
						$retour.="<span id='span_sanction_effectuee_".$lig_sanction->id_sanction."'>";
						if($lig_sanction->effectuee=="O") {
							$retour.="<span style='color:green'> O </span>";
						}
						else {
							$retour.="<span style='color:red'> N </span>";
						}
						$retour.="</span>";
						$retour.="</a>";
					}
					else {
						$retour.=">";
						if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					}
					$retour.="</td>\n";
				}


				if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
					//$retour.="<td><a href='".$_SERVER['PHP_SELF']."?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la sanction n°$lig_sanction->id_sanction' /></a></td>\n";
					$retour.="<td><a href='saisie_sanction.php?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident".add_token_in_url()."' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la ".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction' /></a></td>\n";
				}
				$retour.="</tr>\n";
			}
			$retour.="</table>\n";
		}

		// Exclusions
		$sql="SELECT * FROM s_sanctions s, s_exclusions se WHERE s.id_incident=$id_incident AND s.login='".$ele_login."' AND se.id_sanction=s.id_sanction ORDER BY se.date_debut, se.heure_debut;";
		//$retour.="$sql<br />\n";
		$res_sanction=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_sanction)>0) {
			$retour.="<table class='boireaus' border='1' summary='Exclusions' style='margin:2px;'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Nature</th>\n";
			$retour.="<th>Date début</th>\n";
			$retour.="<th>Heure début</th>\n";
			$retour.="<th>Date fin</th>\n";
			$retour.="<th>Heure fin</th>\n";
			$retour.="<th>Lieu</th>\n";
			$retour.="<th>Travail</th>\n";

			// 20160315
			if(getSettingAOui("active_mod_ooo")) {
				$retour.="<th>Imprimer</th>\n";
			}

			// 20141106
			//if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Effectuée</th>\n";
			//}
			if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Suppr</th>\n";
			}
			$retour.="</tr>\n";
			$alt_b=1;
			while($lig_sanction=mysqli_fetch_object($res_sanction)) {
				$alt_b=$alt_b*(-1);
				$retour.="<tr class='lig$alt_b'>\n";
				//$retour.="<td>Exclusion</td>\n";
				if(($etat_incident!='clos')&&(($_SESSION['statut']!='professeur')&&($_SESSION['statut']!='autre'))) {
					$retour.="<td><a href='saisie_sanction.php?mode=modif&amp;valeur=$lig_sanction->id_nature_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident&amp;ele_login=$ele_login'>".ucfirst($lig_sanction->nature)."</a></td>\n";
				}
				else {
					$retour.="<td>".ucfirst($lig_sanction->nature)."</td>\n";
				}
				$retour.="<td>".formate_date($lig_sanction->date_debut)."</td>\n";
				$retour.="<td>$lig_sanction->heure_debut</td>\n";
				$retour.="<td>".formate_date($lig_sanction->date_fin)."</td>\n";
				$retour.="<td>$lig_sanction->heure_fin</td>\n";
				$retour.="<td>$lig_sanction->lieu</td>\n";
				//$retour.="<td>".nl2br($lig_sanction->travail)."</td>\n";
				$retour.="<td>";

				$tmp_doc_joints=liste_doc_joints_sanction($lig_sanction->id_sanction);
				if(($lig_sanction->travail=="")&&($tmp_doc_joints=="")) {
					$texte="Aucun travail";
				}
				else {
					$texte=nl2br($lig_sanction->travail);
					if($tmp_doc_joints!="") {
						if($texte!="") {$texte.="<br />";}
						$texte.=$tmp_doc_joints;
					}
				}
				$tabdiv_infobulle[]=creer_div_infobulle("div_travail_sanction_$lig_sanction->id_sanction","Travail (".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction)","",$texte,"",20,0,'y','y','n','n',2);

				$retour.=" <a href='#' onmouseover=\"document.getElementById('div_travail_sanction_$lig_sanction->id_sanction').style.zIndex=document.getElementById('sanctions_incident_$id_incident').style.zIndex+1;delais_afficher_div('div_travail_sanction_$lig_sanction->id_sanction','y',10,-40,$delais_affichage_infobulle,$largeur_survol_infobulle,$hauteur_survol_infobulle);\" onclick=\"return false;\">Détails</a>";
				$retour.="</td>\n";

				// 20160315
				if(getSettingAOui("active_mod_ooo")) {
					$retour.="<td><a href='$gepiPath/mod_discipline/saisie_sanction.php?odt=exclusion&id_sanction=".$lig_sanction->id_sanction."&id_incident=$id_incident&ele_login=".$ele_login.add_token_in_url()."' target='_blank'><img src='$gepiPath/images/icons/print.png' class='icone16' alt='Imprimer' /></a></td>\n";
				}

				// 20141106
				// Sanction effectuée
				if($etat_incident=='clos') {
					$retour.="<td>";
					if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					$retour.="</td>\n";
				}
				else {
					$retour.="<td";
					if((in_array($_SESSION['statut'], array('administrateur', 'scolarite', 'cpe')))||
					(($_SESSION['statut']=='professeur')&&(sanction_saisie_par($lig_sanction->id_sanction, $_SESSION['login'])))) {
						$retour.=" title=\"Cliquez pour marquer la sanction comme effectuée ou non effectuée\"";
						if($lig_sanction->effectuee=="O") {
							$valeur_alt="N";
						}
						else {
							$valeur_alt="O";
						}

						$retour.="<a href='#' onclick=\"maj_etat_sanction_effectuee_ou_non($lig_sanction->id_sanction, '$valeur_alt')\">";
						$retour.="<span id='span_sanction_effectuee_".$lig_sanction->id_sanction."'>";
						if($lig_sanction->effectuee=="O") {
							$retour.="<span style='color:green'>O</span>";
						}
						else {
							$retour.="<span style='color:red'>N</span>";
						}
						$retour.="</span>";
						$retour.="</a>";
					}
					else {
						$retour.=">";
						if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					}
					$retour.="</td>\n";
				}


				if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
					//$retour.="<td><a href='".$_SERVER['PHP_SELF']."?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la sanction n°$lig_sanction->id_sanction' /></a></td>\n";
					$retour.="<td><a href='saisie_sanction.php?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident".add_token_in_url()."' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la ".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction' /></a></td>\n";
				}
				$retour.="</tr>\n";
			}
			$retour.="</table>\n";
		}

		// Simple travail
		$sql="SELECT * FROM s_sanctions s, s_travail st WHERE s.id_incident=$id_incident AND s.login='".$ele_login."' AND st.id_sanction=s.id_sanction ORDER BY st.date_retour;";
		//$retour.="$sql<br />\n";
		$res_sanction=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_sanction)>0) {
			$retour.="<table class='boireaus' border='1' summary='Travail' style='margin:2px;'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Nature</th>\n";
			$retour.="<th>Date retour</th>\n";
			$retour.="<th>Travail</th>\n";

			// 20160315
			if(getSettingAOui("active_mod_ooo")) {
				$retour.="<th>Imprimer</th>\n";
			}

			// 20141106
			//if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Effectuée</th>\n";
			//}
			if($etat_incident!='clos') {
				$retour.="<th>Suppr</th>\n";
			}
			$retour.="</tr>\n";
			$alt_b=1;
			while($lig_sanction=mysqli_fetch_object($res_sanction)) {
				$alt_b=$alt_b*(-1);
				$retour.="<tr class='lig$alt_b'>\n";
				if (($etat_incident!='clos')&&(($_SESSION['statut']!='professeur')&&($_SESSION['statut']!='autre'))) {
					$retour.="<td><a href='saisie_sanction.php?mode=modif&amp;valeur=$lig_sanction->id_nature_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident&amp;ele_login=$ele_login'>".ucfirst($lig_sanction->nature)."</a></td>\n";
				}
				else {
					$retour.="<td>".ucfirst($lig_sanction->nature)."</td>\n";
				}
				$retour.="<td>".formate_date($lig_sanction->date_retour)."</td>\n";
				$retour.="<td>";

				$tmp_doc_joints=liste_doc_joints_sanction($lig_sanction->id_sanction);
				if(($lig_sanction->travail=="")&&($tmp_doc_joints=="")) {
					$texte="Aucun travail";
				}
				else {
					$texte=nl2br($lig_sanction->travail);
					if($tmp_doc_joints!="") {
						if($texte!="") {$texte.="<br />";}
						$texte.=$tmp_doc_joints;
					}
				}
				$tabdiv_infobulle[]=creer_div_infobulle("div_travail_sanction_$lig_sanction->id_sanction","Travail (".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction)","",$texte,"",20,0,'y','y','n','n',2);

				$retour.=" <a href='#' onmouseover=\"document.getElementById('div_travail_sanction_$lig_sanction->id_sanction').style.zIndex=document.getElementById('sanctions_incident_$id_incident').style.zIndex+1;delais_afficher_div('div_travail_sanction_$lig_sanction->id_sanction','y',10,-40,$delais_affichage_infobulle,$largeur_survol_infobulle,$hauteur_survol_infobulle);\" onclick=\"return false;\">Détails</a>";
				$retour.="</td>\n";

				// 20160315
				if(getSettingAOui("active_mod_ooo")) {
					$retour.="<td><a href='$gepiPath/mod_discipline/saisie_sanction.php?odt=travail&id_sanction=".$lig_sanction->id_sanction."&id_incident=$id_incident&ele_login=".$ele_login.add_token_in_url()."' target='_blank'><img src='$gepiPath/images/icons/print.png' class='icone16' alt='Imprimer' /></a></td>\n";
				}

				// 20141106
				// Sanction effectuée
				if($etat_incident=='clos') {
					$retour.="<td>";
					if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					$retour.="</td>\n";
				}
				else {
					$retour.="<td";
					if((in_array($_SESSION['statut'], array('administrateur', 'scolarite', 'cpe')))||
					(($_SESSION['statut']=='professeur')&&(sanction_saisie_par($lig_sanction->id_sanction, $_SESSION['login'])))) {
						$retour.=" title=\"Cliquez pour marquer la sanction comme effectuée ou non effectuée\"";
						if($lig_sanction->effectuee=="O") {
							$valeur_alt="N";
						}
						else {
							$valeur_alt="O";
						}

						$retour.="<a href='#' onclick=\"maj_etat_sanction_effectuee_ou_non($lig_sanction->id_sanction, '$valeur_alt')\">";
						$retour.="<span id='span_sanction_effectuee_".$lig_sanction->id_sanction."'>";
						if($lig_sanction->effectuee=="O") {
							$retour.="<span style='color:green'>O</span>";
						}
						else {
							$retour.="<span style='color:red'>N</span>";
						}
						$retour.="</span>";
						$retour.="</a>";
					}
					else {
						$retour.=">";
						if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					}
					$retour.="</td>\n";
				}


				if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
					//$retour.="<td><a href='".$_SERVER['PHP_SELF']."?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la sanction n°$lig_sanction->id_sanction' /></a></td>\n";
					$retour.="<td><a href='saisie_sanction.php?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident".add_token_in_url()."' title='Supprimer la ".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la ".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction' /></a></td>\n";
				}
				$retour.="</tr>\n";
			}
			$retour.="</table>\n";
		}

		// Autres sanctions
		$sql="SELECT * FROM s_sanctions s, s_autres_sanctions sa, s_types_sanctions2 sts WHERE s.id_incident='$id_incident' AND s.login='".$ele_login."' AND sa.id_sanction=s.id_sanction AND sa.id_nature=sts.id_nature ORDER BY sts.nature;";
		//echo "$sql<br />\n";
		$res_sanction=mysqli_query($GLOBALS["mysqli"], $sql);
		if(mysqli_num_rows($res_sanction)>0) {
			$retour.="<table class='boireaus' border='1' summary='Autres ".$mod_disc_terme_sanction."s' style='margin:2px;'>\n";
			$retour.="<tr>\n";
			$retour.="<th>Nature</th>\n";
			$retour.="<th>Description</th>\n";

			// 20160315
			if(getSettingAOui("active_mod_ooo")) {
				$retour.="<th>Imprimer</th>\n";
			}

			// 20141106
			//if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
				$retour.="<th>Effectuée</th>\n";
			//}
			$retour.="<th>Suppr</th>\n";
			$retour.="</tr>\n";
			$alt_b=1;
			while($lig_sanction=mysqli_fetch_object($res_sanction)) {
				$alt_b=$alt_b*(-1);
				$retour.="<tr class='lig$alt_b'>\n";
				$retour.="<td><a href='".$_SERVER['PHP_SELF']."?mode=modif&amp;valeur=".$lig_sanction->id_nature."&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident&amp;ele_login=$ele_login'>$lig_sanction->nature</a></td>\n";

				$retour.="<td>\n";
				$texte=nl2br($lig_sanction->description);
				$tmp_doc_joints=liste_doc_joints_sanction($lig_sanction->id_sanction);
				if($tmp_doc_joints!="") {
					$texte.="<br />";
					$texte.=$tmp_doc_joints;
				}
				$tabdiv_infobulle[]=creer_div_infobulle("div_autre_sanction_$lig_sanction->id_sanction","$lig_sanction->nature (".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction)","",$texte,"",20,0,'y','y','n','n');

				$retour.=" <a href='#' onmouseover=\"document.getElementById('div_autre_sanction_$lig_sanction->id_sanction').style.zIndex=document.getElementById('sanctions_incident_$id_incident').style.zIndex+1;delais_afficher_div('div_autre_sanction_$lig_sanction->id_sanction','y',10,-40,$delais_affichage_infobulle,$largeur_survol_infobulle,$hauteur_survol_infobulle);\" onclick=\"return false;\">Détails</a>";
				$retour.="</td>\n";

				// 20160315
				if(getSettingAOui("active_mod_ooo")) {
					$retour.="<td><a href='$gepiPath/mod_discipline/saisie_sanction.php?odt=autre&id_sanction=".$lig_sanction->id_sanction."&id_incident=$id_incident&ele_login=".$ele_login.add_token_in_url()."' target='_blank'><img src='$gepiPath/images/icons/print.png' class='icone16' alt='Imprimer' /></a></td>\n";
				}

				// 20141106
				// Sanction effectuée
				if($etat_incident=='clos') {
					$retour.="<td>";
					if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					$retour.="</td>\n";
				}
				else {
					$retour.="<td";
					if((in_array($_SESSION['statut'], array('administrateur', 'scolarite', 'cpe')))||
					(($_SESSION['statut']=='professeur')&&(sanction_saisie_par($lig_sanction->id_sanction, $_SESSION['login'])))) {
						$retour.=" title=\"Cliquez pour marquer la sanction comme effectuée ou non effectuée\"";
						if($lig_sanction->effectuee=="O") {
							$valeur_alt="N";
						}
						else {
							$valeur_alt="O";
						}

						$retour.="<a href='#' onclick=\"maj_etat_sanction_effectuee_ou_non($lig_sanction->id_sanction, '$valeur_alt')\">";
						$retour.="<span id='span_sanction_effectuee_".$lig_sanction->id_sanction."'>";
						if($lig_sanction->effectuee=="O") {
							$retour.="<span style='color:green'>O</span>";
						}
						else {
							$retour.="<span style='color:red'>N</span>";
						}
						$retour.="</span>";
						$retour.="</a>";
					}
					else {
						$retour.=">";
						if($lig_sanction->effectuee=="O") {$retour.="<span style='color:green'>O</span>";} else {$retour.="<span style='color:red'>N</span>";}
					}
					$retour.="</td>\n";
				}


				if(($etat_incident!='clos')&&($_SESSION['statut']!='professeur')) {
					//$retour.="<td><a href='".$_SERVER['PHP_SELF']."?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la sanction n°$lig_sanction->id_sanction' /></a></td>\n";
					$retour.="<td><a href='saisie_sanction.php?mode=suppr_sanction&amp;id_sanction=$lig_sanction->id_sanction&amp;id_incident=$id_incident".add_token_in_url()."' title='Supprimer la sanction n°$lig_sanction->id_sanction'><img src='../images/icons/delete.png' width='16' height='16' alt='Supprimer la ".$mod_disc_terme_sanction." n°$lig_sanction->id_sanction' /></a></td>\n";
				}
				$retour.="</tr>\n";
			}
			$retour.="</table>\n";
		}


	}
	return $retour;
}

function get_protagonistes_avec_sanction($id_incident) {
	$retour=array();

	$sql="SELECT DISTINCT sp.* FROM s_protagonistes sp, s_sanctions ss WHERE sp.id_incident='$id_incident' AND sp.id_incident=ss.id_incident ORDER BY qualite, login;";
	//echo "$sql<br />";
	$res=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($res)>0) {
		while($lig=mysqli_fetch_object($res)) {
		  $retour[]=$lig->login;
		}
	}
	return $retour;
}

function sanction_check_delegue($id_sanction, $login) {
	$sql="SELECT 1=1 FROM s_sanctions_check WHERE id_sanction='$id_sanction' AND login='".$login."';";
	//echo "$sql<br />";
	$test=mysqli_query($GLOBALS["mysqli"], $sql);
	if(mysqli_num_rows($test)==0) {
		return false;
	}
	else {
		return true;
	}
}
?>
