<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @author Valery Fremaux valery.fremaux@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */

// Privacy.
$string['privacy:metadata'] = "The Zabbix Report does not store any data belonging to users";

$string['instant_task'] = 'Emission Zabbix continue';
$string['hourly_task'] = 'Emission Zabbix par heure';
$string['daily_task'] = 'Emission Zabbix quotidienne';
$string['weekly_task'] = 'Emission Zabbix hebdomadaire';
$string['monthly_task'] = 'Emission Zabbix mensuelle';

$string['configzabbixapipath'] = 'Chemin vers l\'API Zabbix';
$string['configzabbixapipath_desc'] = 'Un chemin absolu (commençant par un slash) à ajouter au serveur zabbix pour atteindre l\'interface web (souvent /zabbix, sur des installations standard).';
$string['configzabbixprotocol'] = 'Protocole du serveur Zabbix';
$string['configzabbixprotocol_desc'] = 'Protocole pour joindre le serveur. Le serveur zabbix doit être joint directement sans redirection de protocole';
$string['configzabbixserver'] = 'IP du serveur Zabbix';
$string['configzabbixserver_desc'] = 'IP directe du serveur ou proxy zabbix. Un nom de machine connu dans l\'environnement local DNS est possible';
$string['configzabbixhostname'] = 'Nom de l\'hôte moodle dans zabbix';
$string['configzabbixhostname_desc'] = 'Le nom d\'hote associé au modèle MOODLE dans la configuration du serveur zabbix';
$string['configzabbixsendercmd'] = 'Commande zabbix_sender';
$string['configzabbixsendercmd_desc'] = 'L\'emplacement de la commande "sender" pour l\'émission des données à Zabbix';
$string['configzabbixadminusername'] = 'Nom d\'utilisateur Zabbix';
$string['configzabbixadminusername_desc'] = 'Il s\'agit du nom de l\'utiisateur administrateur capable d\'opérer l\'API Zabbix';
$string['configzabbixadminpassword'] = 'Mot de passe Zabbix';
$string['configzabbixadminpassword_desc'] = 'Le mot de passe de l\'utiisateur administrateur capable d\'opérer l\'API Zabbix';
$string['configzabbixallowedcronperiod'] = 'Période admise pour le cron (en min)';
$string['configzabbixallowedcronperiod_desc'] = 'La période du cron doit entre en général très courte (1m). Certaines installations doivent pourtant pouvoir admettre des périodes plus longues.';
$string['configzabbixgroups'] = 'Groupes zabbix';
$string['configzabbixgroups_desc'] = 'Groupes (liste à virgules) auxquel rattacher l\'hôte représentant ce Moodle.';
$string['configzabbixinterfacedef'] = 'Définition de l\'interface locale';
$string['configzabbixinterfacedef_desc'] = 'La méthode pour informer Zabbix de l\'interface réseau à utiliser pour communiquer avec ce serveur.';
$string['configzabbixtellithasstarted'] = 'En exploitation';
$string['configzabbixtellithasstarted_desc'] = 'En activant cette case, vous informez Zabbix que cette plate-forme est en exploitation.
Ceci permet des statistiques de "zone" dans le cas de constellations de moodles. Vous pouvez alternativement proposer une heuristique
par requête SQL pour déterminer cet état. L\'activation explicite l\'emporte.';
$string['configzabbixtellithasstartedsql'] = 'Heuristique SQL pour l\'exploitation';
$string['configzabbixtellithasstartedsql_desc'] = 'Une requête SQL délivrant un résultat nommé "started" déterminant l\'état
de démarrage de l\'exploitation.';

$string['configuserrolepolicy'] = 'Détermination des rôles utilisateur';
$string['configuserrolepolicy_desc'] = 'Méthode de détermination des rôles utilisateur. Certaines méthodes nécessitent des configurations particulières.';
$string['entpolicy'] = 'Résolution pour les moodle inclus dans les ENT (basé sur champs de profil).';
$string['standardpolicy'] = 'Résolution basée sur les assignations de rôle standard de moodle.';
$string['register'] = 'Enregistrer le site';
$string['registerinzabbix'] = 'Suivez ce lien pour <a href="/report/zabbix/register.php">enregistrer ce site dans Zabbix</a>';
$string['errornoremotelogin'] = 'Moodle n\'a pas pu se connecter à l\'administration de Zabbix.';
$string['loginok'] = 'API connecté à Zabbix';
$string['creating'] = 'L\'hôte {$a} est nouveau dans Zabbix. Création... ';
$string['created'] = '... Créé.';
$string['updating'] = 'L\'hôte {$a} existe dans Zabbix. Mise à jour... ';
$string['updated'] = '... Mis à jour.';
$string['notconfigured'] = 'Le serveur Zabbix n\'est pas (totalement) configué.';
$string['configure'] = 'Configurer le serveur Zabbix';
$string['zabbixserversettings'] = 'Réglages du serveur zabbix';

$string['pluginname'] = 'Zabbix Sender';

$string['bypublicip'] = 'Par ip publique';
$string['byinternalip'] = 'Par ip interne';
$string['bydns'] = 'Par DNS';

$string['notstartedyet'] = 'En attente d\'exploitation';
$string['started'] = 'En exploitation';

include(__DIR__.'/pro_additional_strings.php');