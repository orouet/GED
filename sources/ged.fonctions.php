<?PHP


/**
 * Fonctions de base
 * @package GED
 * @author Olivier ROUET
 * @version 1.0.0
 */


/**
 * Liste le contenu d'un dossier
 *
 * @param string $dossier
 * @return mixed
 */
function GED_dossierLister($dossier = '')
{

	// initialisation des variables
	$sortie = false;
	
	// traitement
	if (is_dir($dossier)) {
	
		// Ouverture du dossier
		$pointeur = @opendir($dossier);
		
		// on regarde si le dossier a été ouvert avec succès
		if ($pointeur !== false) {
		
			// intialisation du tableau de sortie
			$sortie = array();
			
			// on parcourt les éléments contenus dans le dossier
			while ($element = @readdir($pointeur)) {
			
				// on élimine les éléments inutiles
				if ($element != '.' && $element != '..') {
				
					// chemin complet
					$chemin = $dossier . '/' . $element;
					
					// on regarde si l'élément est un dossier
					if (is_dir($chemin)) {
					
						$sortie[$element] = [
							'nom' => $element,
							'chemin' => $chemin,
							'dossier' => $dossier,
							'type' => 'dossier',
							'contenu' => GED_dossierLister($chemin),
							'informations' => []
						];
					
					} else {
					
						$sortie[$element] = [
							'nom' => $element,
							'chemin' => $chemin,
							'dossier' => $dossier,
							'type' => 'document',
							'contenu' => false,
							'informations' => GED_documentInformationslire($chemin)
						];
					
					}
				
				}
			
			}
			
			closedir($pointeur);
		
		}
	
	}
	
	// sortie
	return $sortie;

}


/**
 * Renvoie les informations concernant un document
 *
 * @param string $cible
 * @return mixed
 */
function GED_documentInformationslire($cible)
{

	// initialisation des variables
	$sortie = false;
	
	// traitement
	if (is_file($cible)) {
	
		$sortie['taille'] = filesize($cible);
		$sortie['empreinte'] = sha1_file($cible);
		
		
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo, $cible);
		finfo_close($finfo);
		
		$sortie['mime'] = $mime;
		
		switch ($mime) {
		
			case 'image/jpeg' :
			
				$metas = getimagesize($cible);
				
				if ($metas !== false) {
				
					$sortie['metas'] = $metas;
				
				}
			
			break;
		
		}
	
	}
	
	// sortie
	return $sortie;

}


?>