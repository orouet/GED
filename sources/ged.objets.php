<?PHP


/**
 * Objets de base
 * @package GED
 * @author Olivier ROUET
 * @version 1.0.0
 */


/**
 * classe GED_Controleur
 *
 */
class GED_Controleur
{

	/**
	 * Objet MySQLi
	 *
	 * @access public
	 * @var mixed
	 */
	public $connexion;
	
	
	/**
	 * Adresse du SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_serveur;
	
	
	/**
	 * Identifiant de connexion au SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_identifiant;
	
	
	/**
	 * Mot de passe de connexion au SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_motdepasse;
	
	
	/**
	 * Base de données à utiliser dans le SGBD
	 *
	 * @access public
	 * @var string
	 */
	public $sgbd_base;
	
	
	/**
	 * Constructeur
	 *
	 * @param string $serveur
	 * @param string $identifiant
	 * @param string $motdepasse
	 * @param string $base
	 */
	public function __construct($serveur, $identifiant, $motdepasse, $base)
	{
	
		// intialisation des variables
		$this->connexion = false;
		$this->sgbd_serveur = $serveur;
		$this->sgbd_identifiant = $identifiant;
		$this->sgbd_motdepasse = $motdepasse;
		$this->sgbd_base = $base;
		
		$this->connecter();
	
	}
	
	
	/**
	 * Connection au SGBD
	 *
	 * @return boolean
	 */
	public function connecter()
	{
	
		// initialisation des variables
		$sortie = false;
		
		// traitement
		$connexion = new mysqli(
			$this->sgbd_serveur,
			$this->sgbd_identifiant,
			$this->sgbd_motdepasse,
			$this->sgbd_base
		);
		
		if ($connexion->connect_error) {
		
			die('Connect Error (' . $connexion->connect_errno . ') ' . $connexion->connect_error);
		
		} else {
		
			$this->connexion = $connexion;
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Cherche et renvoie un document
	 *
	 * @param string $empreinte
	 * @return mixed
	 */
	public function documentChercher($empreinte)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `ged__documents` WHERE empreinte = '" . ($empreinte) . "';";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$nombre = mysqli_num_rows($resultat);
			
			if ($nombre === 1) {
			
				$sortie = $resultat->fetch_assoc();
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Ajoute un document à un lot
	 *
	 * @param string $lot_id
	 * @param string $document
	 * @return mixed
	 */
	public function documentCreer($lot_id, $document)
	{
	
		// intialisation des variables
		$sortie = false;
		$chemin = CHEMIN_STOCKAGE . $lot_id . '/';
		$nom = $document['nom'];
		$source = $document['chemin'];
		$empreinte = $document['informations']['empreinte'];
		$cible = $chemin . $empreinte . '.jpg';
		
		// traitement
		$requete = "
			INSERT INTO `ged__documents` (
				`id`,
				`ts`,
				`lots_id`,
				`nom`,
				`empreinte`
			) VALUE (
				null,
				null,
				'" . ($lot_id) . "',
				'" . addslashes($nom) . "',
				'" . ($empreinte) . "'
			);
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$insert_id = $this->connexion->insert_id;
			
			$document_ged = $this->documentLire($insert_id);
			
			// Copie du document
			$copie = copy($source, $cible);
			
			if ($copie === true) {
			
				$sortie = $document_ged;
			
			}
		
		} else {
		
			die($document['nom'] . " : insert KO");
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Lit un document
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function documentLire($id)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `ged__documents` WHERE id = " . ($id) . ";";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = $resultat->fetch_assoc();
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Vérifie une empreinte
	 *
	 * @param string $empreinte
	 * @return mixed
	 */
	public function empreintesVerifier($empreintes)
	{
	
		// intialisation des variables
		$sortie = false;
		$sql_in = "";
		$correspondances = array();
		
		// traitement
		$sql_in = "'";
		$sql_in .= implode("','", $empreintes);
		$sql_in .= "'";
		
		
		$requete = "
			SELECT
				*
			FROM
				`ged__documents`
			WHERE
				empreinte IN (" . $sql_in . ")
			;
		";
		// print($requete);
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			while ($ligne = $resultat->fetch_assoc()) {
			
				$cle = $ligne['empreinte'];
				$correspondances[$cle] = $ligne;
			
			}
		
		}
		
		// var_dump($correspondances);
		
		$sortie = $correspondances;
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Ajoute un lot
	 *
	 * @param string $nom
	 * @return mixed
	 */
	public function lotCreer($nom)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		// On tente d'insérer un nouveau lot
		$requete = "
			INSERT INTO `ged__lots` (
				`id`,
				`ts`,
				`nom`
			) VALUE (
				null,
				null,
				'" . addslashes($nom) . "'
			);
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$insert_id = $this->connexion->insert_id;
			
			$lot_ged = $this->lotLire($insert_id);
			
			// Création du dossier de stockage
			$chemin = CHEMIN_STOCKAGE . $insert_id . '/';
			$creation = mkdir($chemin, 0777, false);
			
			if ($creation === true) {
			
				$sortie = $lot_ged;
			
			} else {
			
				die("Impossible de créer le dossier " . $chemin);
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Cherche et renvoie un lot
	 *
	 * @param string $nom
	 * @return mixed
	 */
	public function lotChercher($nom)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `ged__lots` WHERE nom = '" . addslashes($nom) . "';";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$nombre = $resultat->num_rows;
			
			if ($nombre === 1) {
			
				$sortie = $resultat->fetch_assoc();
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Lit un lot
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function lotLire($id)
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "SELECT * FROM `ged__lots` WHERE id = " . ($id) . ";";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = $resultat->fetch_assoc();
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}
	
	
	/**
	 * Liste les lots
	 *
	 * @return mixed
	 */
	public function lotsLister()
	{
	
		// intialisation des variables
		$sortie = false;
		
		// traitement
		$requete = "
			SELECT
				l.*,
				(SELECT count(d.id) as documents FROM`ged__documents` d WHERE d.lots_id = l.id) AS documents
			FROM
				`ged__lots` l
			;
		";
		
		$resultat = $this->connexion->query($requete);
		
		if ($resultat !== false) {
		
			$sortie = array();
			
			while($ligne = $resultat->fetch_assoc()) {
			
				$sortie[] = $ligne;
			
			}
		
		} else {
		
			die($requete);
		
		}
		
		// sortie
		return $sortie;
	
	}


}


?>