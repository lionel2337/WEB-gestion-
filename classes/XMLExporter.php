<?php
/**
 * Classe XMLExporter
 * Gère l'exportation et l'importation de données au format XML.
 */
class XMLExporter {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Exporte la liste des employés actifs au format XML
     */
    public function exporterEmployes() {
        try {
            $stmt = $this->db->query("SELECT e.*, d.nom_departement, p.titre_poste, s.nom_succursale 
                                     FROM employes e 
                                     LEFT JOIN departements d ON e.id_departement = d.id_departement 
                                     LEFT JOIN postes p ON e.id_poste = p.id_poste 
                                     LEFT JOIN succursales s ON e.id_succursale = s.id_succursale 
                                     WHERE e.est_supprime = 0 AND e.statut_employe != 'archive'");
            $employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $xml = new DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;

            $root = $xml->createElement('global_enterprise_corp');
            $xml->appendChild($root);

            // Métadonnées de l'export
            $meta = $xml->createElement('metadata');
            $meta->appendChild($xml->createElement('export_date', date('Y-m-d H:i:s')));
            $meta->appendChild($xml->createElement('total_records', count($employes)));
            $meta->appendChild($xml->createElement('generated_by', $_SESSION['username'] ?? 'System'));
            $root->appendChild($meta);

            // Liste des employés
            $list = $xml->createElement('employes');
            foreach ($employes as $emp) {
                $item = $xml->createElement('employe');
                $item->setAttribute('id', $emp['id_employe']);
                $item->setAttribute('matricule', $emp['matricule']);

                $item->appendChild($xml->createElement('nom', htmlspecialchars($emp['nom'])));
                $item->appendChild($xml->createElement('prenom', htmlspecialchars($emp['prenom'])));
                $item->appendChild($xml->createElement('email', htmlspecialchars($emp['email_professionnel'])));
                $item->appendChild($xml->createElement('telephone', htmlspecialchars($emp['telephone_principal'])));
                $item->appendChild($xml->createElement('sexe', htmlspecialchars($emp['sexe'])));
                $item->appendChild($xml->createElement('date_naissance', $emp['date_naissance']));
                $item->appendChild($xml->createElement('date_embauche', $emp['date_embauche']));
                $item->appendChild($xml->createElement('departement', htmlspecialchars($emp['nom_departement'])));
                $item->appendChild($xml->createElement('poste', htmlspecialchars($emp['titre_poste'])));
                $item->appendChild($xml->createElement('succursale', htmlspecialchars($emp['nom_succursale'])));
                $item->appendChild($xml->createElement('statut', htmlspecialchars($emp['statut_employe'])));
                $item->appendChild($xml->createElement('type_employe', htmlspecialchars($emp['type_employe'])));
                $item->appendChild($xml->createElement('banque', htmlspecialchars($emp['banque'] ?? '')));
                $item->appendChild($xml->createElement('numero_compte', htmlspecialchars($emp['numero_compte_bancaire'] ?? '')));

                $list->appendChild($item);
            }
            $root->appendChild($list);

            Logger::log('Export XML employés', 'export', 'employes', null, count($employes) . " employés exportés");

            return $xml->saveXML();
        } catch (Exception $e) {
            error_log('Erreur export XML: ' . $e->getMessage());
            return false;
        }
    }
}
