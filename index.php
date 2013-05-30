<?php

/* (webpage retriever by Timo Van Neerden; http://lehollandaisvolant.net/contact December 2012)
 * last updated : December, 10th, 2012
 *
 * This piece of software is under the WTF Public Licence. 
 * Everyone is permitted to copy and distribute verbatim or modified 
 * copies of this program, under the following terms of the WFTPL :
 *
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
 *   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION 
 *
 *  0. You just DO WHAT THE FUCK YOU WANT TO.
 *
 *  Process improvement by Jerry Wham
 *  Last update December, 26th, 2012
 */

#TODO
/*
- remplacer les liens relatifs par les liens absolus (ne chercher que les liens relatifs, uri)
- gestion des pages DL (classement ?) => FAIT
- gestion de la taille max des fichiers à télécharger 
*/

// PHP 5.1.2 minimum required

// Check php version
function checkphpversion()
{
    if (version_compare(PHP_VERSION, '5.1.2') < 0)
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Your server supports php '.PHP_VERSION.'. Respawn requires at last php 5.1.2, and thus cannot run. Sorry.';
        exit;
    }
}
// Only for debugging
ini_set('error_reporting', E_ALL);
// Session management
define('INACTIVITY_TIMEOUT',3600); // (in seconds). If the user does not access any page within this time, his/her session is considered expired.
ini_set('session.use_cookies', 1);       // Use cookies to store session.
ini_set('session.use_only_cookies', 1);  // Force cookies for session (phpsessionID forbidden in URL)
ini_set('session.use_trans_sid', false); // Prevent php to use sessionID in URL if cookies are disabled.
//ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'].'/../sessions');
session_name('Respawn');
session_start();

// ------------------------------------------------------------------------------------------
// MANAGE FOLDERS
//
class manageFolder {

    public $path = null; # chemin vers le dossier data
    public $aDirs = array(); # liste des dossiers et sous dossiers
    public $dir = null; # chemin vers le dossier courant
    /*
    $imgfile = 'loadinfo.net.gif';
    $imgbinary = fread(fopen($imgfile, "r"), filesize($imgfile));
    echo base64_encode($imgbinary);
    */
    /* IMAGES base64 encoded */
    public $loadinfo = 'data:image/gif;base64,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';


    public $trash = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAfCAYAAAD5h919AAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAAWdEVYdENyZWF0aW9uIFRpbWUAMTIvMjkvMTEsPOvqAAAAfUlEQVRIie3VwQnAIAwF0B/p3WEyYfczu7QT2ENRpBexTXJp/i1C8kBFCQth5jrWIkKznlrvlrQCfQkx8wEgGztnckAAILttnd8ZWQPt1m1t4Xl1FUOA49YFZAeJCI1v2qx+DWkloIACCugPUH91rb7yUkp85XrQbjC/z7wAqpYh5maai80AAAAASUVORK5CYII=';

    public $location = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABoAAAAmCAYAAADa8osYAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAAWdEVYdENyZWF0aW9uIFRpbWUAMTIvMjkvMTEsPOvqAAACJ0lEQVRIib2X0ZGbQAyGP5h7xx3EHRwz8nsogQ7u0gElkApCCbgDl3D3jiZ2BbE7MBU4Dyx3GItlOTL5Zzxj70r6V9JKWkd4ICIbIHefDEhGIi3wBhyAg6pexzZutxsAkYekBArD+BRaoFLVMohIRLbuhM+BBGOcgFxVz5NEIpLShSLUiym0QKaqxweiAJI+H0f3O8XO2x1Z0zRHgKfBRj2h1AKlqlaWNREpgNLQTZzNFCB2wiV2Tk50ITBJANxe5mTHeN7tdiVA5K7w2TjRR5ynSIbwhL4FtjFdjVghK0NJAJxsaWwlQN4TjdH6wuUhq+g8GCOP6eI7xmEpyYxuFmOH7byCyNJN4hUGF2GKKF1h09SNgYuxnq0gsnQvMXbyElfEi+B0rJzXkevWf4zNf1uwrp3/NHQT4M0Z+CoJQNU0zTVyghu6rvzNEGyBim6o3U1Qp1cwPSBPTdOk8Dgmfs8c/p3POtkC3z2yd2NiPPgK4NcMWSh+qGrtG+U18LKSZK+qr/A5yq2CLbBnSyhOPckQD0Qu4Tl2F55Diz0N7Bbkrnz5BaKif/2MMfmuAxCRM/aVt3BR1e140ZejIV4DSWZlvR5BsFfvqppZG6EeQViu6jmBWY9g1iszNz2WeAR+r3x7HwjyCEBErhgjQFU3Pr2lHkHXwUPWTCwhqv8Lkav4/WBpb/3DW03kUA++L3rJBl+GHiJyBFDVoCdZfxmeZuQsLH6TA/wFPZXkEkNIeRQAAAAASUVORK5CYII=';

    public $info = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAkCAYAAACAGLraAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNXG14zYAAAAWdEVYdENyZWF0aW9uIFRpbWUAMTIvMjkvMTEsPOvqAAABm0lEQVRIiZWV0VHDQAxEXzL8EypIOohnVACmAtIBoQN3gKkAqIDQAenADWgm7iCpgLgDPix7zufz5bxfyWm02jut5AWJEJEcKIAVcAI+VfW8SEwugTfvuAHymwQisgL+JsLHZYKALBJ7TiGIoUl9gytwHwj9pCooaB/NRQ0USQpMxQbY0bbxrKqH1NwoUtqYd79VtUoiMLkl8OKFamCnqufuYPSIIrKntaqfDLAFDu7BgMAs+024ZR0egwRWufP7F+O2BbG05NwqAzypahHJuQwIbFgO9v9VVSsRyZi+xq+vIAPWwLtjjn1EQeUTABxVtXTOdxPJjaoOFZg5+ormgXVK9V6Bql4TqoN3/57AwyyCgZVvrK9aVUfbyVcQq34IHfoEeYRgJH+Ogos7gUECs3OS+6YUzHr9OQSNu4lEpHC3VDeNG6bd11e3xI+Qgpj8ypIzh6x37sKCJ9p1FcKDQ7SlndrSVzCV3Ji6roA/tdxFpEPb1m5T1QT2xNIJxnAEcm9qBwr2tHf0jXQBythnrJ9Ga6UrsQp9iXz8AzE4g/UlBH//AAAAAElFTkSuQmCC';

    /**
     * Constructeur qui initialise la variable de classe
     *
     * @param   path    répertoire racine des data
     * @param   dir     dossier de recherche
     * @return  null
     * @author  Stephane F
     **/
    public function __construct($path,$dir) {

        # Initialisation
        $this->path = $path;
        if (is_dir($this->path)) {
            $this->aDirs = $this->_getAllDirs($this->path);
        } else {
            $this->creer_dossier($this->path);
            $this->aDirs = $this->_getAllDirs($this->path);
        }
        $this->dir = $dir;
    }

    /**
     * Fonction récursive qui retourne un tableau de tous les dossiers et sous dossiers dans un répertoire
     *
     * @param   dir     repertoire de lecture
     * @param   level   profondeur du repertoire
     * @return  folders tableau contenant la liste de tous les dossiers et sous dossiers
     * @author  Stephane F
     **/
    private function _getAllDirs($dir,$level=0) {

        # Initialisation
        $folders = array();
        # Ouverture et lecture du dossier demandé
        if($handle = opendir($dir)) {
            while (FALSE !== ($folder = readdir($handle))) {
                if($folder[0] != '.') {
                    if(is_dir(($dir!=''?$dir.'/':$dir).$folder)) {
                        $dir = (substr($dir, -1)!='/' AND $dir!='') ? $dir.'/' : $dir;
                        $path = str_replace($this->path, '',$dir.$folder.'/');
                        $folders[] = array(
                                'level' => $level,
                                'name' => $folder,
                                'path' => $path
                            );

                        $folders = array_merge($folders, $this->_getAllDirs($dir.$folder, $level+1) );
                    }
                }
            }
            closedir($handle);
        }
        # On retourne le tableau
        return $folders;
    }


    /**
     * Fonction qui formate l'affichage de la liste déroulante des dossiers
     *
     * @param $aDirs array tableau contenant tous les dossiers
     * @param $dir string nom du dossier sélectionné
     * 
     * @return  string  chaine formatée à afficher
     * @author  Stephane F
     **/
    public function contentFolder($numForm = '') {

        $str  = "\n".'<select class="folder" id="folder'.$numForm.'" size="1" name="folder'.$numForm.'">'."\n";
        $selected = (empty($this->dir)?'selected="selected" ':'');
        $str .= '<option '.$selected.'value=".">|. (Root) &nbsp; </option>'."\n";
        # Dir non vide
        if(!empty($this->aDirs)) {
            foreach($this->aDirs as $k => $v) {
                if (!preg_match('#^[0-9]{4}(-[0-9]{2}){5}$#', $v['name'])) {
                    $prefixe = '|&nbsp;&nbsp;';
                    $i = 0;
                    while($i < $v['level']) {
                        $prefixe .= '&nbsp;&nbsp;';
                        $i++;
                    }
                    $selected = ($v['path']==$this->dir?'selected="selected" ':'');
                    $str .= '<option '.$selected.'value="'.$v['path'].'">'.$prefixe.$v['name'].'</option>'."\n";
                } 
            }
        }
        $str  .= '</select>'."\n";

        # On retourne la chaine
        return $str;
    }


    /**
     * Fonction qui supprime un fichier
     *
     * @param   files   liste des fichier à supprimer
     * @return  boolean faux si erreur sinon vrai
     * @author  Stephane F
     **/
    public function deleteFiles($files) {

        $count = 0;
        foreach($files as $file) {
            # protection pour ne pas supprimer un fichier en dehors de $path.$dir
            $file=basename($file);
            if(!unlink($this->path.$file)) {
                $count++;
            }
        }

        if(sizeof($files)==1) {
            if($count==0)
                return TRUE;
            else
                return FALSE;
        }
        else {
            if($count==0)
                return TRUE;
            else
                return FALSE;
        }
    }


    /**
     * Fonction récursive qui supprime tous les dossiers et les fichiers d'un répertoire
     *
     * @param   deldir  répertoire de suppression
     * @return  boolean résultat de la suppression
     * @author  Stephane F
     **/
    private function _deleteDir($deldir) { #fonction récursive

        if(is_dir($deldir) AND !is_link($deldir)) {
            if($dh = opendir($deldir)) {
                while(FALSE !== ($file = readdir($dh))) {
                    if($file != '.' AND $file != '..') {
                        $this->_deleteDir($deldir.'/'.$file);
                    }
                }
                closedir($dh);
            }
            return rmdir($deldir);
        }
        return unlink($deldir);
    }

    /**
     * Fonction qui supprime un dossier et son contenu
     *
     * @param   deleteDir   répertoire à supprimer
     * @return  boolean faux si erreur sinon vrai
     * @author  Stephane F
     **/
    public function deleteDir($deldir) {

        # suppression du dossier des images et de son contenu
        if($this->_deleteDir($this->path.$deldir))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Fonction qui crée un nouveau dossier
     *
     * @param   newdir  nom du répertoire à créer
     * @return  boolean faux si erreur sinon vrai
     * @author  Stephane F
     **/
    public function creer_dossier($newdir, $indexfile = TRUE) {

        if ($newdir == $this->path && is_dir($this->path)) return TRUE;

        if ($newdir != $this->path) {
            if ($this->dir != '/' && $this->dir != '') {
                $newdir = $this->path.$this->dir.$newdir;
            } else {
                $newdir = $this->path.'/'.$newdir;
            }
        }

        if ( !is_dir($newdir) ) {
            if (mkdir($newdir, 0777, TRUE) === TRUE) {
                chmod($newdir, 0777);
                if ($indexfile == TRUE) touch($newdir.'/index.html'); // make a index.html file : avoid the possibility of listing folder's content
                return TRUE;
            } else {
                return FALSE;
            }
        }
        return TRUE; // if folder already exists
    }

    public function chooseFolder($folder,$folder1,$folder2) {
        if (($folder != $folder1 && $folder != $folder2) || ($folder == $folder1 && $folder == $folder2)) {
            return $folder;
        }
        if ($folder == $folder1 && $folder != $folder2) {
            return $folder2;
        }
        if ($folder != $folder1 && $folder == $folder2) {
            return $folder1;
        }
    }

    /**
     * Méthode qui déplace un ou plusieurs fichiers
     *
     * @param   dirToMove       liste des fichier à déplacer
     * @param   src_dir     répertoire source
     * @param   dst_dir     répertoire destination
     * @return  boolean     faux si erreur sinon vrai
     * @author  Stephane F
     **/
    public function moveFiles($dirToMove, $src_dir, $dst_dir) {

        if($dst_dir=='.') $dst_dir='';
        $foldersToDelete = array();

        $count = 0;
        foreach($dirToMove as $dir) {

            $sourceFolder = str_replace('//', '/', $this->path.'/'.$src_dir.$dir);
            $destinationFolder = str_replace('//', '/', $this->path.'/'.$dst_dir.$dir);

            if (!is_dir($destinationFolder)) {
                if (mkdir($destinationFolder, 0705, TRUE) === TRUE) {
                chmod($destinationFolder, 0705);
                }
            }

            $files = scandir($sourceFolder);

            foreach ($files as $file) {
                if (is_file($sourceFolder.'/'.$file)) {

                    # protection pour ne pas déplacer un fichier en dehors de $this->path.$this->dir
                    $file=basename($file);

                    # Déplacement du fichier
                    if(is_readable($sourceFolder.'/'.$file)) {
                        $result = rename($sourceFolder.'/'.$file, $destinationFolder.'/'.$file);
                        $foldersToDelete[] = $src_dir.$dir;
                        $count++;
                    }
                }
            }
        }
        foreach ($foldersToDelete as $dir) {                
            if (is_dir($this->path.$dir)) $this->deleteDir($dir);
        }
    }

    /**
     * Controle le nom d'un fichier ou d'un dossier
     *
     * @param   string  nom d'un fichier
     * @return  boolean validité du nom du fichier ou du dossier
    */
    public function checkSource($src, $type='dir') {

        if (is_null($src) OR !strlen($src) OR substr($src,-1,1)=="." OR false!==strpos($src, "..")) {
            return false;
        }

        if($type=='dir')
            $regex = ",(/\.)|[[:cntrl:]]|(//)|(\\\\)|([\\:\*\?\"\<\>\|]),";
        elseif($type=='file')
            $regex = ",[[:cntrl:]]|[/\\:\*\?\"\<\>\|],";

        if (preg_match($regex, $src)) {
            return false;
        }
        return true;
    }

    /**
     * Fonction qui formate une chaine de caractères en supprimant des caractères non valides
     *
     * @param   str         chaine de caracères à formater
     * @param   charset     charset à utiliser dans le formatage de la chaine (par défaut utf-8)
     * @return  string      chaine formatée
     **/
    public function removeAccents($str,$charset='utf-8') {

        $str = htmlentities($str, ENT_NOQUOTES, $charset);
        $str = preg_replace('#\&([A-za-z])(?:acute|cedil|circ|grave|ring|tilde|uml|uro)\;#', '\1', $str);
        $str = preg_replace('#\&([A-za-z]{2})(?:lig)\;#', '\1', $str); # pour les ligatures e.g. '&oelig;'
        $str = preg_replace('#\&[^;]+\;#', '', $str); # supprime les autres caractères
        return $str;
    }

    /**
     * Fonction qui convertit une chaine de caractères au format valide pour un nom de fichier
     *
     * @param   str         chaine de caractères à formater
     * @return  string      nom de fichier valide
     **/
    public function title2filename($str,$charset='utf-8') {

        $str = strtolower($this->removeAccents($str,$charset));
        $str = str_replace('|','',$str);
        $str = preg_replace('/\.{2,}/', '.', $str);
        $str = preg_replace('/[^[:alnum:]|.|_]+/',' ',$str);
        return strtr(ltrim(trim($str),'.'), ' ', '-');
    }

    // Returns the server URL (including port and http/https), without path.
    // eg. "http://myserver.com:8080"
    // You can append $_SERVER['SCRIPT_NAME'] to get the current script URL.
    public function serverUrl()
    {
        $https = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS'])=='on')) || $_SERVER["SERVER_PORT"]=='443'; // HTTPS detection.
        $serverport = ($_SERVER["SERVER_PORT"]=='80' || ($https && $_SERVER["SERVER_PORT"]=='443') ? '' : ':'.$_SERVER["SERVER_PORT"]);
        return 'http'.($https?'s':'').'://'.$_SERVER["SERVER_NAME"].$serverport;
    }

    public function url_parts() {
        $url_p['s']    = parse_url($GLOBALS['url'], PHP_URL_SCHEME); $url_p['s']   = (is_null($url_p['s'])) ? '' : $url_p['s'];
        $url_p['h']    = parse_url($GLOBALS['url'], PHP_URL_HOST);   $url_p['h']   = (is_null($url_p['h'])) ? '' : $url_p['h'];
        $url_p['p']    = parse_url($GLOBALS['url'], PHP_URL_PORT);   $url_p['p']   = (is_null($url_p['p'])) ? '' : ':'.$url_p['p'];
        $url_p['pat']  = parse_url($GLOBALS['url'], PHP_URL_PATH);   $url_p['pat'] = (is_null($url_p['pat'])) ? '' : $url_p['pat'];
        $url_p['file'] = pathinfo($url_p['pat'], PATHINFO_BASENAME);
        return $url_p;
    }

    //
    // Gets external file by URL. 
    // Make a stream context (better).
    //

    public function get_external_file($url, $timeout) {
        $context = stream_context_create(array('http'=>array('timeout' => $timeout))); // Timeout : time until we stop waiting for the response.
        $data = @file_get_contents($url, false, $context, -1, 4000000); // We download at most 4 Mb from source.
        if (isset($data) and isset($http_response_header) and isset($http_response_header[0]) and (strpos($http_response_header[0], '200 OK') !== FALSE) ) {

            // cherche le charset spécifié dans le code HTML.
            // récupère la balise méta tout entière, dans $meta
            preg_match('#<meta .*charset=.*>#Usi', $data, $meta);

            // si la balise charset a été trouvée, on tente d’isoler l’encodage.
            if (!empty($meta[0])) {
                // récupère juste l’encodage utilisé, dans $enc
                preg_match('#charset="?(.*)"#si', $meta[0], $enc);
                // regarde si le charset a été trouvé, sinon le fixe à UTF-8
                $html_charset = (!empty($enc[1])) ? strtolower($enc[1]) : 'utf-8';
            } else { $html_charset = 'utf-8';$enc[1] = ''; }

        $data = str_replace('charset='.$enc[1], 'charset='.$html_charset, $data);

        $backhome = '<div id="Respawn" style="background:#f0f0f0;height:40px;position:fixed;bottom:-10px;width:100%;left:0;overflow:hidden;opacity:0.6;filter:alpha(opacity=60);text-align:right;z-index:999;"><a href="'.$GLOBALS['ROOT'].'">Home</a></div>';

        $data = str_replace('</body>', $backhome.'</body>', $data);

            return $data;
        }
        else {
            return FALSE;
        }
    }


    //
    // PARSE TAGS AND LISTE DOWNLOADABLE CONTENT IN ARRAY
    // Also modify html source code to replace absolutes URLs with local URIs.
    //

    public function list_retrievable_data($url, &$data) {
        $url_p = $this->url_parts();

        $retrievable = array();

        // cherche les balises 'link' qui contiennent  un rel="(icon|favicon|stylesheet)" et un href=""
        // (on ne cherche pas uniquement le "href" sinon on se retrouve avec les flux RSS aussi)
        $matches = array();
        preg_match_all('#<\s*link[^>]+rel=["\'][^"\']*(icon|favicon|stylesheet)[^"\']*["\'][^>]*>#Si', $data, $matches, PREG_SET_ORDER);
        // dans les link avec une icone, stylesheet, etc récupère l’url.
        foreach($matches as $i => $key) {
            $type =  (strpos($key[1], 'stylesheet') !== FALSE) ? 'css' : 'other';
            if ( (preg_match_all('#(href|src)=["\']([^"\']*)["\']#i', $matches[$i][0], $matches_attr, PREG_SET_ORDER) === 1) ) {
                $retrievable = $this->add_table_and_replace($data, $retrievable, $matches[$i][0], $matches_attr[0][2], $url_p, $type);
            }
        }

        // recherche les images, scripts, audio & videos HTML5.
        // dans les balises, récupère l’url/uri contenue dans les src="".
        // le fichier sera téléchargé.
        // Le nom du fichier sera modifié pour être unique, et sera aussi modifié dans le code source.
        $matches = array();
        /*preg_match_all('#<\s*(source|audio|img|script|video)[^>]+src="([^"]*)"[^>]*>#Si', $data, $matches, PREG_SET_ORDER);

        foreach($matches as $i => $key) {
            if (preg_match('#^data:#', $matches[$i][2])) break;
            $retrievable = $this->add_table_and_replace($data, $retrievable, $matches[$i][0], $matches[$i][2], $url_p, 'other');
        }*/
        preg_match_all('#<\s*(source|audio|img|script|video)[^>]+src=(["\'])([^\2]+?)\2[^>]*>#Si', $data, $matches, PREG_SET_ORDER);

        foreach($matches as $i => $key) {
        if (preg_match('#^data:#', $matches[$i][3])) break;
            $retrievable = $this->add_table_and_replace($data, $retrievable, $matches[$i][0], $matches[$i][3], $url_p, 'other');
        }

        // Dans les balises <style>, remplace les url() et src()
        $matches = array();
        preg_match_all('#<\s*style[^>]*>(.*?)<\s*/\s*style[^>]*>#is', $data, $matches, PREG_SET_ORDER);

        // pour chaque élement <style>
        foreach($matches as $i => $value) {
            $matches_url = array();
            preg_match_all('#url\s*\(("|\')?([^\'")]*)(\'|")?\)#i', $matches[$i][1], $matches_url, PREG_SET_ORDER);

            // pour chaque URL/URI
            foreach ($matches_url as $j => $valuej) {
                if (preg_match('#^data:#', $matches_url[$j][2])) break;
                $retrievable = $this->add_table_and_replace($data, $retrievable, $matches[$i][1], $matches_url[$j][2], $url_p, 'other');
            }
        }

        // recherche les url dans les CSS inlines.
        $matches = array();
        // pour chaque élement contenant un style=""
        preg_match_all('#<\s*[^>]+style="([^"]*url\s*\(([^)]*)\)[^"]*)"[^>]*>+#is', $data, $matches, PREG_SET_ORDER);
        foreach($matches as $i => $value) {
            $matches_url = array();

            // pour chaque URL/URI trouvé
            preg_match_all('#url\s*\(("|\')?([^\'")]*)(\'|")?\)#i', $matches[$i][1], $matches_url, PREG_SET_ORDER);

            foreach ($matches_url as $j => $valuej) {
                if (preg_match('#^data:#', $matches_url[$j][2])) break; // if BASE64 data, dont download.
                $retrievable = $this->add_table_and_replace($data, $retrievable, $matches[$i][1], $matches_url[$j][2], $url_p, 'other');
            }
        }
        return $retrievable;
    }

    public function absolutes_links(&$data) {
        $url_p = $this->url_parts();
        // cherche les balises 'a' qui contiennent un href
        $matches = array();
        preg_match_all('#<\s*a[^>]+href=["\'](([^"\']*))["\'][^>]*>#Si', $data, $matches, PREG_SET_ORDER);

        // ne conserve que les liens ne commençant pas par un protocole « protocole:// » ni par une ancre « # »
        foreach($matches as $i => $link) {
            $link[1] = trim($link[1]);
            if (!preg_match('#^(([a-z]+://)|(\#))#', $link[1]) ) {

                // absolute path w/o HTTP : add http.
                if (preg_match('#^//#', $link[1])) {
                    $matches[$i][1] = $url_p['s'].':'.$link[1];
                }
                // absolute local path : add http://domainname.tld
                elseif (preg_match('#^/#', $link[1])) {
                    $matches[$i][1] = $url_p['s'].'://'.$url_p['h'].$link[1];
                }
                // relative local path : add http://domainename.tld/path/
                else {
                    $uuu = (strlen($url_p['file']) == 0 or preg_match('#/$#', $url_p['pat'])) ? $GLOBALS['url'] : substr($GLOBALS['url'], 0, -strlen($url_p['file'])) ;

                    $matches[$i][1] = $uuu.$link[1];
                }
                $new_match = str_replace($matches[$i][2], $matches[$i][1], $matches[$i][0]);
                $data = str_replace($matches[$i][0], $new_match, $data);
            }




        }

    }

    function add_table_and_replace(&$data, $retrievable, &$match1, $match, $url_p, $type) {
        // get the filenam (basename)
        $nom_fichier = (preg_match('#^https?://#', $match)) ? pathinfo(parse_url($match, PHP_URL_PATH), PATHINFO_BASENAME) : pathinfo($match, PATHINFO_BASENAME);
        // get the URL. For relatives URL, uses the GLOBALS[url] tu make the complete URL
        // the files in CSS are relative to the CSS !
        if (preg_match('#^https?://#', $match)) { // url
            $url_fichier = $match;
            //echo "u ";
        }
        elseif (preg_match('#^//#', $match)) { // absolute path w/o HTTP
            $url_fichier = $url_p['s'].':'.$match;
            //echo "h ";
        }
        elseif (preg_match('#^/#', $match)) { // absolute local path
            $url_fichier = $url_p['s'].'://'.$url_p['h'].$match;
            //echo "l ";
        }
        else { // relative local path
            //echo "r ";
            //echo '<pre>';print_r($url_p);

            $globurl = $url_p['s'].'://'.$url_p['h'].$url_p['pat'];
            $uuu = (strlen($url_p['file']) == 0 or preg_match('#/$#', $globurl)) ? $globurl : substr($globurl, 0, -strlen($url_p['file']));

            $url_fichier = $uuu . substr($match, 0, -strlen($nom_fichier)).$nom_fichier;
        }


        $url_fichier = html_entity_decode(urldecode($url_fichier));
        //echo $url_fichier."<br/>\n";


        // new rand name, for local storage.
        $nouveau_nom = $this->rand_new_name($nom_fichier);
        if ($type == 'css') {
            $nouveau_nom = $nouveau_nom.'.css';
        }
        $add = TRUE;

        // avoids downloading the same file twice.
        foreach ($retrievable as $key => $item) {
            if ($item['url_fichier'] == $url_fichier) {
                $nouveau_nom = $item['nom_destination'];
                $add = FALSE;
                break;
            }
        }

        // if we do want to download it, we add to the array.
        if ($add === TRUE) {
            $retrievable[] = array(
                'url_origine' => $match,
                'url_fichier' => $url_fichier,
                'nom_fich_origine' => $nom_fichier,
                'nom_destination' => $nouveau_nom,
                'type' => $type
                );
        }

        // replace the URL with the new filename in the &data.
        $new_match = str_replace($match, $nouveau_nom, $match1);
        $data = str_replace($match1, $new_match, $data);
        $match1 = $new_match;

        return $retrievable;
    }


    function rand_new_name($name) {
    //  return 'f_'.str_shuffle('abcd').mt_rand(100, 999).'--'.''.$name;
        return 'f_'.str_shuffle('abcd').mt_rand(100, 999).'--'.preg_replace('#[^\w.]#', '_', $name);
    }
}
// ------------------------------------------------------------------------------------------

// PHP Settings
ini_set('max_input_time','60');  // High execution time in case of problematic imports/exports.
checkphpversion();
//error_reporting(E_ALL^E_WARNING);  // See all error except warnings.
error_reporting(-1); // See all errors (for debugging only)
date_default_timezone_set('UTC');


// CONFIGURABLE OPTIONS
$GLOBALS['data_folder'] = 'data';
$GLOBALS['config']['DATADIR'] = 'config'; // Data subdirectory
$GLOBALS['config']['CONFIG_FILE'] = $GLOBALS['config']['DATADIR'].'/config.php'; // Configuration file (user login/password)
$GLOBALS['config']['IPBANS_FILENAME'] = $GLOBALS['config']['DATADIR'].'/ipbans.php'; // File storage for failures and bans.
$GLOBALS['config']['BAN_AFTER'] = 4;        // Ban IP after this many failures.
$GLOBALS['config']['BAN_DURATION'] = 1800;  // Ban duration for IP address after login failures (in seconds) (1800 sec. = 30 minutes)
$GLOBALS['config']['OPEN_RESPAWN'] = false; // If true, anyone can add/edit/delete links without having to login

if (is_file($GLOBALS['config']['CONFIG_FILE'])) {
    include $GLOBALS['config']['CONFIG_FILE'];  // Read login/password hash into $GLOBALS.
}

if (!isset($_SESSION['folder'])) $_SESSION['folder'] = '';
$Manager = new manageFolder($GLOBALS['data_folder'],$_SESSION['folder']);

if (!isset($Manager->dir) && !is_dir($GLOBALS['data_folder']) ) {
    if(!$Manager->creer_dossier($GLOBALS['data_folder'], TRUE)) { echo '<script language="JavaScript">alert(\'Can\'t create '.$GLOBALS['data_folder'].' folder.\');</script>';exit(); }
}
if (isset($Manager->dir) && ($Manager->dir == '' || $Manager->dir == '/') && !is_dir($GLOBALS['data_folder']) ) {
    if(!$Manager->creer_dossier($GLOBALS['data_folder'], TRUE)) { echo '<script language="JavaScript">alert(\'Can\'t create '.$GLOBALS['data_folder'].' folder.\');</script>';exit(); }
}
if (!isset($GLOBALS['ROOT']) ) $GLOBALS['ROOT'] = $Manager->serverUrl().$_SERVER['PHP_SELF'];

// Force cookie path (but do not change lifetime)
$cookie=session_get_cookie_params();
session_set_cookie_params($cookie['lifetime'],dirname($_SERVER["SCRIPT_NAME"]).'/'); // Default cookie expiration and path.

// Directories creations (Note that your web host may require differents rights than 705.)
if (!is_dir($GLOBALS['config']['DATADIR'])) { mkdir($GLOBALS['config']['DATADIR'],0705); chmod($GLOBALS['config']['DATADIR'],0705); }
if (!is_file($GLOBALS['config']['DATADIR'].'/.htaccess')) { file_put_contents($GLOBALS['config']['DATADIR'].'/.htaccess',"Allow from none\nDeny from all\n"); } // Protect data files.
if (!is_file($GLOBALS['config']['DATADIR'].'/index.html')) { file_put_contents($GLOBALS['config']['DATADIR'].'/index.html',""); } // Protect data files.


// Brute force protection system
// Several consecutive failed logins will ban the IP address for 30 minutes.
if (!is_file($GLOBALS['config']['IPBANS_FILENAME'])) file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export(array('FAILURES'=>array(),'BANS'=>array()),true).";\n?>");

include $GLOBALS['config']['IPBANS_FILENAME'];

// Token management for XSRF protection
 // Token should be used in any form which acts on data (create,update,delete,import...).       
if (!isset($_SESSION['tokens'])) $_SESSION['tokens']=array();  // Token are attached to the session.

// ------------------------------------------------------------------------------------------
// SI UNE DEMANDE CONCERNANT LES DOSSIERS EST POSTEE
//
# Sécurisation du chemin du dossier
if(isset($_POST['folder']) AND $_POST['folder']!='.' AND !$Manager->checkSource($_POST['folder'])) {
    $_POST['folder']='.';
}

if(!empty($_POST['folder'])) {
    $_SESSION['currentfolder']= (isset($Manager->dir)?$Manager->dir:'');
    $Manager->dir = ($_POST['folder'] == '.' ? '/' : '/'.$_POST['folder']);
    $Manager->dir = str_replace('//', '/', $Manager->dir);
}

if(!empty($_POST['btn_newfolder']) AND !empty($_POST['newfolder']) AND $_POST['newfolder'] != '.') {//Création d'un nouveau dossier
    $newdir = $Manager->title2filename(trim($_POST['newfolder']));
    if ($newdir == $GLOBALS['data_folder']) {
        echo '<script language="JavaScript">alert("Can\'t create \''.$newdir.'\' folder. \''.$newdir.'\' is a reserved name.");document.location="'.$GLOBALS['ROOT'].'";</script>';
        exit();
    }
    if($Manager->creer_dossier($newdir)) {
        $Manager->dir = $Manager->dir.'/'.$newdir.'/';
    }
    $Manager->dir = str_replace('//', '/', $Manager->dir);
    header('Location: '.$GLOBALS['ROOT']);
    exit;
} elseif(!empty($_POST['btn_delete']) AND !empty($_POST['folder']) AND $_POST['folder']!='.') {//Suppression d'un dossier et de tout son contenu
    if($Manager->deleteDir($_POST['folder'])) {
        $Manager->dir = '';
        echo '<script language="JavaScript">alert(\''.$_POST['folder'].' folder deleted.\');document.location=\''.$GLOBALS['ROOT'].'\';</script>';
    }
    exit;
} elseif(isset($_POST['selection']) AND ($_POST['selection'][0] == 'move' OR $_POST['selection'][1] == 'move') AND isset($_POST['idFile'])) {//Déplacement du contenu d'un dossier vers un autre
    $dest = $Manager->chooseFolder($_POST['folder'],$_POST['folder1'],$_POST['folder2']);
    $Manager->moveFiles($_POST['idFile'], $Manager->dir, $dest);
    $_SESSION['folder'] = ($dest == '.' ? '/' : $dest);
    header('Location: '.$GLOBALS['ROOT']);
    exit;
}elseif(isset($_POST['selection']) AND ($_POST['selection'][0] == 'delete' OR $_POST['selection'][1] == 'delete') AND isset($_POST['idFile'])) {
    foreach ($_POST['idFile'] as $key => $value) {//Suppresion d'éléments sélectionnés dans la liste
        $value = str_replace('//','/',$Manager->dir.$value);
        if(!$Manager->deleteDir($value)) {
            $notDelete = TRUE;
        }
    }

    if(!isset($notDelete)) {
        echo '<script language="JavaScript">alert(\'Selection deleted.\');document.location=\''.$GLOBALS['ROOT'].'\';</script>';
    }
    exit;
}
// ------------------------------------------------------------------------------------------
// Building pages functions:
/* This class is in charge of building the final page.
   p = new pageBuilder;
   p.assign('myfield','myvalue');
   p.renderPage('mytemplate');
   
*/
class pageBuilder extends manageFolder{

    private $tpl; // RainTPL template like

    public $var = array();
    public $path = null; //Chemin vers le dossier data

    function __construct($path)
    {
        $this->tpl=false;
        $this->path = $path;
        
    } 

    private function initialize()
    {    
        $this->tpl = true;
        return;    
    }

    public function header($page)
    {
        switch ($page) {
            case 'addlink':
                $form = 'q';
                break;
            case 'loginform':
                $form = 'login';
                break;
            case 'changepassword':
                $form = 'oldpassword';
                break;
            case 'install':
                $form = 'setlogin';
                break;
            
            default:
                $form = 'login';
                break;
        }
        $header ='<!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8" />
            <title>Respawn – a PHP WebPage Saver</title>
            <link rel="stylesheet" type="text/css" href="style.css"/>
            </head>
        <body onload="document.form.'.$form.'.focus();">
        <div id="orpx_nav-bar">
    ';
        return $header;
    }

    public function footer()
    {
        $footer = (isset($this->var['do']) && $this->var['do'] == 'login' ? "\t".'</div>'."\n" : '').'
        </body>
    </html>
    ';
        return $footer;
    }
    
    // The following assign() method is basically the same as RainTPL (except that it's lazy)
    public function assign($what,$where)
    {
        if ($this->tpl===false) $this->initialize(); // Lazy initialization
        $this->TPLassign($what,$where);
    }

    /**
     * Assign variable
     * eg.  $t->assign('name','mickey');
     *
     * @param mixed $variable_name Name of template variable or associative array name/value
     * @param mixed $value value assigned to this variable. Not set if variable_name is an associative array
     */

    function TPLassign( $variable, $value = null ){
        if( is_array( $variable ) )
            $this->var += $variable;
        else
            $this->var[ $variable ] = $value;
    }

    
    // Render a specific page (using a template).
    // eg. pb.renderPage('picwall')
    public function renderPage($page)
    {
        if ($this->tpl===false) $this->initialize(); // Lazy initialization
        $this->draw($page);
    }

    //Templates
    public function draw($page) {
        $form = $this->header($page);
        if (!$this->isLoggedIn() && $page != 'install') $page = 'loginform';
        switch ($page) {
            case 'addlink':
            $form .='
    <form method="get" action="'.$GLOBALS['ROOT'].'" style="text-align:center" name="form">
        <p>
            <input id="____q" type="text" size="70" name="q" value="" placeholder="URL from the page to download" />
            <input type="submit" value="Retrieve" onclick="if(document.getElementById(\'____q\').value == \'\') {return false;}else{document.getElementById(\'wait\').style.display=\'block\';document.getElementById(\'panel\').style.display=\'none\';}"/>
            <input type="hidden" name="token" value="'.$this->var['token'].'"/>
        </p>
    </form>
    <p><a href="'.$GLOBALS['ROOT'].'?do=tools">Tools</a>&nbsp;'.($GLOBALS['disablesessionprotection']==FALSE ? '-&nbsp;<a href="'.$GLOBALS['ROOT'].'?do=logout">Logout</a>' : '').'</p>
    ';
                break;
            
            case 'loginform':
            if(!$this->ban_canLogin()) {
                $form .='<p>You have been banned from login after too many failed attempts. Try later.</p>';
            } else {
                $form .='
    <form method="post" action="'.$GLOBALS['ROOT'].'" style="text-align:center" name="form">
        <p>
            <label for="log">Login:</label><input type="text" id="log" name="login" tabindex="1"/><br/>
            <label for="pass">Password :</label><input type="password" id="pass" name="password" tabindex="2"/><br/>
            <input type="hidden" name="token" value="'.$this->var['token'].'"/><br/>';
            if (isset($this->var['q'])) :
            $form .= '<input type="hidden" name="q" value="'.$this->var['q'].'"/><br/>';
            endif;
            if (isset($this->var['source'])) :
            $form .= '<input type="hidden" name="source" value="'.$this->var['source'].'"/><br/>';
            endif;
            $form .= '<label for="longlastingsession" style="min-width:250px;">&nbsp;Stay signed in (Do not check on public computers)</label><input style="margin:10 0 0 40;" type="checkbox" name="longlastingsession" id="longlastingsession"  tabindex="3"/><br/>
            <input type="submit" value="Login" tabindex="4" ';if (isset($this->var['source']) && $this->var['source']=='bookmarklet') { $form .= 'onclick="document.getElementById(\'wait\').style.display=\'block\';document.getElementById(\'panel\').style.display=\'none\';"'; } $form .='/>
        </p>
    </form>
    ';          
            }
                break;
            
            case 'tools':
                $form .= ' <a href="'.$GLOBALS['ROOT'].'">Home</a><br/>';
                if(!$GLOBALS['config']['OPEN_RESPAWN']) {
                $form .= '<a href="?do=changepasswd"><b>Change password</b>  <span>: Change your password.</span></a><br><br>';
                }
                $form .='
    <a href="?do=configure"><b>Configure your Respawn</b> <span>:  Change protection of your Respawn</span></a><br><br>
    <a class="smallbutton" onclick="alert(\'Drag this link to your bookmarks toolbar, or right-click it and choose Bookmark This Link...\');return false;" href="javascript:javascript:(function(){var%20url%20=%20location.href;window.open(\''.$GLOBALS['ROOT'].'?do=addlink;q=\'%20+%20encodeURIComponent(url)+\'&source=bookmarklet\',\'_blank\',\'menubar=no,height=390,width=600,toolbar=no,scrollbars=no,status=no,dialog=1\');})();"><b>Respawn</b></a> <span>&#x21D0; Drag this link to your bookmarks toolbar (or right-click it and choose Bookmark This Link....).<br>&nbsp;&nbsp;&nbsp;&nbsp;Then click "Respawn" button in any page you want to save.</span>
    </p>
    ';
                break;
            
            case 'changepassword':
            $form .='
    <form method="get" action="'.$GLOBALS['ROOT'].'" name="form" id="changepasswordform" style="text-align:center">
        <p>
            <a href="'.$GLOBALS['ROOT'].'">Home</a><br/>
            <input type="hidden" name="do" value="changepasswd"/>
            <label for="old">Old password :</label><input type="password" id="old" name="oldpassword"/><br/>
            <label for="new">New password :</label><input type="password" id="new" name="setpassword"/><br/>
            <input type="hidden" name="token" value="'.$this->var['token'].'"/>
            <input type="submit" name="Save" value="Save password"/>
        </p>
    </form>
    ';

                break;
            
            case 'configure':
            $form .='   
    <form method="get" action="'.$GLOBALS['ROOT'].'" style="text-align:center" name="form">
        <p>
            <a href="'.$GLOBALS['ROOT'].'">Home</a><br/>
            <input type="hidden" name="do" value="configure"/>
            Security: <input type="checkbox" name="disablesessionprotection" id="disablesessionprotection"'; if(!empty($GLOBALS['disablesessionprotection']) ) { $form .='checked="checked"';} $form .='/>&nbsp;Disable session cookie hijacking protection<br/>(Check this if you get disconnected often or if your IP address changes often.)<br/>
            <input type="hidden" name="rec" value="true"/>
            <input type="hidden" name="token" value="'.$this->var['token'].'"/>
            <input type="submit" value="Save configuration"/>
        </p>
    </form>
    ';
                break;
            
            case 'wait':            
            case 'linklist':            
                # code...
                break;
            
            case 'install':
            $form .='
    <h1>Respawn</h1>
    <p>It looks like it\'s the first time you run Respawn. Please configure it:</p>
    <form method="post" action="'.$GLOBALS['ROOT'].'" name="form" id="installform">
        <p>
            <label for="log">Login:</label><input type="text" id="log" name="setlogin" size="30" /><br/>
            <label for="pass">Password:</label><input type="password" id="pass" name="setpassword" size="30" /><br/>
            <input type="hidden" name="token" value="'.$this->var['token'].'"/>
            <input type="submit" name="Save" value="Save configuration" />
        </p>
    </form>
    </div>
    ';
        echo $form .= $this->footer();
        exit();
                break;
            
            default:
                # code...
                break;
        }
        echo $form;
    }

            // Log to text file
    public function logm($message)
        {
            $t = strval(date('Y/m/d_H:i:s')).' - '.$_SERVER["REMOTE_ADDR"].' - '.strval($message)."\n";
            file_put_contents($GLOBALS['config']['DATADIR'].'/log.txt',$t,FILE_APPEND);
        }

        // Returns the IP address of the client (Used to prevent session cookie hijacking.)
    public function allIPs()
        {
            $ip = $_SERVER["REMOTE_ADDR"];
            // Then we use more HTTP headers to prevent session hijacking from users behind the same proxy.
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip=$ip.'_'.$_SERVER['HTTP_X_FORWARDED_FOR']; }
            if (isset($_SERVER['HTTP_CLIENT_IP'])) { $ip=$ip.'_'.$_SERVER['HTTP_CLIENT_IP']; }
            return $ip;
        }

        // Check that user/password is correct.
    public function check_auth($login,$password)
        {
            $hash = sha1($password.$login.$GLOBALS['salt']);
            if ($login==$GLOBALS['login'] && $hash==$GLOBALS['hash']) {   // Login/password is correct.
                $_SESSION['uid'] = sha1(uniqid('',true).'_'.mt_rand()); // generate unique random number (different than phpsessionid)
                $_SESSION['ip']=$this->allIPs();                // We store IP address(es) of the client to make sure session is not hijacked.
                $_SESSION['username']=$login;
                $_SESSION['expires_on']=time()+INACTIVITY_TIMEOUT;  // Set session expiration.
                $_SESSION['folder'] = '/';
                $this->logm('Login successful');
                return True;
            }
            $this->logm('Login failed for user '.$login);
            return False;
        }

        // Returns true if the user is logged in.
    public function isLoggedIn()
        {
            // If no authentification required
            if ($GLOBALS['config']['OPEN_RESPAWN']) return true;

            // Run config screen if first run:
            if (!is_file($GLOBALS['config']['CONFIG_FILE'])) {
                $this->install();
                exit();
            }

            if (empty($_SESSION['uid']) || ($GLOBALS['disablesessionprotection']==false && $_SESSION['ip']!=$this->allIPs()) || time()>=$_SESSION['expires_on'])
            {
                $this->logout();
                return false;
            }
            if (!empty($_SESSION['longlastingsession']))  $_SESSION['expires_on']=time()+$_SESSION['longlastingsession']; // In case of "Stay signed in" checked.
            else $_SESSION['expires_on']=time()+INACTIVITY_TIMEOUT; // Standard session expiration date.

            return true;
        }

        // Force logout.
    public function logout() { 
            if (isset($_SESSION)) { unset($_SESSION['uid']); unset($_SESSION['ip']); unset($_SESSION['username']);}  
            // If we are called from the bookmarklet, we must close the popup:
            if (isset($_GET['source']) && $_GET['source']=='selfclose') { 
                echo '<script language="JavaScript">self.close();</script>'; exit(); 
            }
        }


        // ------------------------------------------------------------------------------------------

        // Signal a failed login. Will ban the IP if too many failures:
    public function ban_loginFailed()
        {
            $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
            if (!isset($gb['FAILURES'][$ip])) $gb['FAILURES'][$ip]=0;
            $gb['FAILURES'][$ip]++;
            if ($gb['FAILURES'][$ip]>($GLOBALS['config']['BAN_AFTER']-1))
            {
                $gb['BANS'][$ip]=time()+$GLOBALS['config']['BAN_DURATION'];
                $this->logm('IP address banned from login');
            }
            $GLOBALS['IPBANS'] = $gb;
            file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
        }

        // Signals a successful login. Resets failed login counter.
    public function ban_loginOk()
        {
            $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
            unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
            $GLOBALS['IPBANS'] = $gb;
            file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
        }

        // Checks if the user CAN login. If 'true', the user can try to login.
    public function ban_canLogin()
        {
            $ip=$_SERVER["REMOTE_ADDR"]; $gb=$GLOBALS['IPBANS'];
            if (isset($gb['BANS'][$ip]))
            {
                // User is banned. Check if the ban has expired:
                if ($gb['BANS'][$ip]<=time())
                {   // Ban expired, user can try to login again.
                    $this->logm('Ban lifted.');
                    unset($gb['FAILURES'][$ip]); unset($gb['BANS'][$ip]);
                    file_put_contents($GLOBALS['config']['IPBANS_FILENAME'], "<?php\n\$GLOBALS['IPBANS']=".var_export($gb,true).";\n?>");
                    return true; // Ban has expired, user can login.
                }
                return false; // User is banned.
            }
            return true; // User is not banned.
        }

        // ------------------------------------------------------------------------------------------
        // Token management for XSRF protection
        // Token should be used in any form which acts on data (create,update,delete,import...).
        
        // Returns a token.
    public function getToken()
        {
            $rnd = sha1(uniqid('',true).'_'.mt_rand());  // We generate a random string.
            $_SESSION['tokens'][$rnd]=1;  // Store it on the server side.
            return $rnd;
        }

        // Tells if a token is ok. Using this function will destroy the token.
        // true=token is ok.
    public function tokenOk($token)
        {
            if (isset($_SESSION['tokens'][$token]))
            {
                unset($_SESSION['tokens'][$token]); // Token is used: destroy it.
                return true; // Token is ok.
            }
            return false; // Wrong token, or already used.
        }

    // Installation
    // This function should NEVER be called if the file data/config.php exists.
    function install()
    {
        // If config file already exists
        if (is_file($GLOBALS['config']['CONFIG_FILE'])) {header('Location :'.$GLOBALS['ROOT']);exit();}
        // On free.fr host, make sure the /sessions directory exists, otherwise login will not work.
        if ($this->endsWith($_SERVER['SERVER_NAME'],'.free.fr') && !is_dir($_SERVER['DOCUMENT_ROOT'].'/sessions')) mkdir($_SERVER['DOCUMENT_ROOT'].'/sessions',0705);

        if (!empty($_POST['setlogin']) && !empty($_POST['setpassword']))
        {
            // Everything is ok, let's create config file.
            $GLOBALS['login'] = $_POST['setlogin'];
            $GLOBALS['salt'] = sha1(uniqid('',true).'_'.mt_rand()); // Salt renders rainbow-tables attacks useless.
            $GLOBALS['hash'] = sha1($_POST['setpassword'].$GLOBALS['login'].$GLOBALS['salt']);
            $this->writeConfig();
            echo '<script language="JavaScript">alert("Respawn is now configured. Please enter your login/password and start saving your pages !");document.location=\'?do=login\';</script>';
            exit();
        }

        // Display config form:
        $this->assign('token',$this->getToken());
        $this->renderPage('install');
    }

    // Re-write configuration file according to globals.
    // Requires some $GLOBALS to be set (login,hash,salt,title).
    // If the config file cannot be saved, an error message is dislayed and the user is redirected to "addlink" menu.
    // (otherwise, the function simply returns.)
    public function writeConfig()
    {
        if (is_file($GLOBALS['config']['CONFIG_FILE']) && !$this->isLoggedIn()) die('You are not authorized to alter config.'); // Only logged in user can alter config.
        if (empty($GLOBALS['redirector'])) $GLOBALS['redirector']='';
        if (empty($GLOBALS['disablesessionprotection'])) $GLOBALS['disablesessionprotection']=false;
        $config='<?php $GLOBALS[\'login\']='.var_export($GLOBALS['login'],true).'; $GLOBALS[\'hash\']='.var_export($GLOBALS['hash'],true).'; $GLOBALS[\'salt\']='.var_export($GLOBALS['salt'],true).'; ';
        $config .= '$GLOBALS[\'redirector\']='.var_export($GLOBALS['redirector'],true).'; ';
        $config .= '$GLOBALS[\'disablesessionprotection\']='.var_export($GLOBALS['disablesessionprotection'],true).'; ';
        $config .= ' ?>';
        if (!file_put_contents($GLOBALS['config']['CONFIG_FILE'],$config) || strcmp(file_get_contents($GLOBALS['config']['CONFIG_FILE']),$config)!=0)
        {
            echo '<script language="JavaScript">alert("Respawn could not create the config file. Please make sure Respawn has the right to write in the folder is it installed in.");document.location=\'?do=addlink\';</script>';
            exit;
        }
    }


    // Tells if a string start with a substring or not.
    public function startsWith($haystack,$needle,$case=true) {
        if($case){return (strcmp(substr($haystack, 0, strlen($needle)),$needle)===0);}
        return (strcasecmp(substr($haystack, 0, strlen($needle)),$needle)===0);
    }

    // Tells if a string ends with a substring or not.
    public function endsWith($haystack,$needle,$case=true) {
        if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
        return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
    }

    /**
     * Fonction qui affiche une liste de sélection
     *
     * @param   name        nom de la liste
     * @param   array       valeurs de la liste sous forme de tableau (nom, valeur)
     * @param   selected    valeur par défaut
     * @param   readonly    vrai si la liste est en lecture seule (par défaut à faux)
     * @param   class       class css à utiliser pour formater l'affichage
     * @param   id          si à vrai génère un id, sinon utilise l'id donné s'il n'est pas null
     * @return  stdout
     **/
    public function printSelect($name, $array, $selected='', $readonly=false, $class='', $id=true) {

        if(!is_array($array)) $array=array();

        $id = (($id === true) ?' id="id_'.$name.'"': (!empty($id) ? ' id="'.$id.'"' : '') );
        if($readonly)
            echo '<select'.$id.' name="'.$name.'" disabled="disabled" class="readonly">'."\n";
        else
            echo '<select'.$id.' name="'.$name.'"'.($class!=''?' class="'.$class.'"':'').'>'."\n";
        foreach($array as $a => $b) {
            if(is_array($b)) {
                echo '<optgroup label="'.$a.'">'."\n";
                foreach($b as $c=>$d) {
                    if($c == $selected)
                        echo "\t".'<option value="'.$c.'" selected="selected">'.$d.'</option>'."\n";
                    else
                        echo "\t".'<option value="'.$c.'">'.$d.'</option>'."\n";
                }
                echo '</optgroup>'."\n";
            } else {
                if($a == $selected)
                    echo "\t".'<option value="'.$a.'" selected="selected">'.$b.'</option>'."\n";
                else
                    echo "\t".'<option value="'.$a.'">'.$b.'</option>'."\n";
            }
        }
        echo '</select>'."\n";
    }


    // ------------------------------------------------------------------------------------------
    // Render HTML page (according to URL parameters and user rights)
    public function displayPage()
    {

        // -------- Display login form.
        if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=login'))
        {
            if ($GLOBALS['config']['OPEN_RESPAWN']) { header('Location: ?'); exit; }  // No need to login for open Respawn
            $token=''; if ($this->ban_canLogin()) $token=$this->getToken(); // Do not waste token generation if not useful.
            
            $this->assign('token',$token);
            $this->assign('do','login');
            $this->renderPage('loginform');
            echo $this->footer();
            exit();
        }
        // -------- User wants to logout.
        if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=logout'))
        {
            $this->logout();
            header('Location: '.$GLOBALS['ROOT']);
            exit();
        }

        // -------- Handle other actions allowed for non-logged in users:
        if (!$this->isLoggedIn() )
        {

            $token=''; if ($this->ban_canLogin()) $token = $this->getToken(); // Do not waste token generation if not useful.

            // User tries to post new link but is not loggedin:
            // Show login screen, then redirect to ?post=...
            if (isset($_GET['q']))
            {
                $_GET['do'] = 'login';
                $this->assign('q',$_GET['q']);
                (!empty($_GET['source'])? $this->assign('source',$_GET['source']):'');
            }
            $this->assign('token',$token);
            $this->assign('do','login');
            $this->renderPage('loginform');
            echo $this->footer();
            exit();
        } else {

            // -------- All other functions are reserved for the registered user:

            // -------- Display the Tools menu if requested (import/export/bookmarklet...)
            if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=tools'))
            {
                $this->renderPage('tools');
            }

            // -------- User wants to change his/her password.
            if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=changepasswd'))
            {
                if ($GLOBALS['config']['OPEN_RESPAWN']) {
                    echo '<script language="JavaScript">alert("You are not supposed to change a password on an Open Respawn.");document.location=\'?do=addlink\';</script>';
                    exit;
                }
                if (!empty($_GET['setpassword']) && !empty($_GET['oldpassword']))
                {
                    if (!$this->tokenOk($_GET['token'])) die('Wrong token.'); // Go away !

                    // Make sure old password is correct.
                    $oldhash = sha1($_GET['oldpassword'].$GLOBALS['login'].$GLOBALS['salt']);
                    if ($oldhash!=$GLOBALS['hash']) { echo '<script language="JavaScript">alert("The old password is not correct.");document.location=\'?do=changepasswd\';</script>'; exit; }
                    // Save new password
                    $GLOBALS['salt'] = sha1(uniqid('',true).'_'.mt_rand()); // Salt renders rainbow-tables attacks useless.
                    $GLOBALS['hash'] = sha1($_GET['setpassword'].$GLOBALS['login'].$GLOBALS['salt']);
                    $this->writeConfig();
                    echo '<script language="JavaScript">alert("Your password has been changed.");document.location=\'?do=addlink\';</script>';
                    exit;
                }
                else // show the change password form.
                {
                    $this->assign('token',$this->getToken());
                    $this->renderPage('changepassword');
                }
            }

            // -------- User wants to change configuration
            if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=configure'))
            {
                if (!empty($_GET['rec']) )
                {
                    if (!$this->tokenOk($_GET['token'])) die('Wrong token.'); // Go away !
                    $GLOBALS['disablesessionprotection']=!empty($_GET['disablesessionprotection']);
                    $this->writeConfig();
                    echo '<script language="JavaScript">alert("Configuration was saved.");document.location=\'?do=addlink\';</script>';
                    exit;
                }
                else // Show the configuration form.
                {
                    $this->assign('token',$this->getToken());
                    $this->renderPage('configure');
                }
            }

            // -------- User wants to add a link without using the bookmarklet: show form.
            if (isset($_SERVER["QUERY_STRING"]) && $this->startswith($_SERVER["QUERY_STRING"],'do=addlink') && $this->isLoggedIn())
            {
                $this->assign('token',$this->getToken());
                $this->renderPage('addlink');
            }

            // -------- User wants to add a link without using the bookmarklet: show form.
            if (isset($_SERVER["QUERY_STRING"]) && empty($_SERVER["QUERY_STRING"]) )
            {
                $this->assign('token',$this->getToken());
                $this->renderPage('addlink');
            }

            // -------- User clicked the "Delete" button 
            if (isset($_GET['suppr']) and $torem = $_GET['suppr'] and $torem != '' and $_GET['suppr'] != 'config') {
                if (!$this->tokenOk($_GET['token'])) die('Wrong token.');
                // We do not need to ask for confirmation:
                // - confirmation is handled by javascript
                // - we are protected from XSRF by the token.
                //////
                
                $torem = str_replace('data','',htmlspecialchars($_GET['suppr']));

                $this->deleteDir($torem);

                header('Location:'.$GLOBALS['ROOT']);
                exit();
            }
        }
    }
}


// ------------------------------------------------------------------------------------------
// We are building the final page
$PAGE = new pageBuilder($GLOBALS['data_folder']);
// Handling of old config file which do not have the new parameters.
if (empty($GLOBALS['disablesessionprotection'])) $GLOBALS['disablesessionprotection']=false;
if ($GLOBALS['disablesessionprotection'] === true) $GLOBALS['config']['OPEN_RESPAWN']=true;

// ------------------------------------------------------------------------------------------
// Process login form: Check if login/password is correct.
if (isset($_POST['login'])){

    if (!$PAGE->ban_canLogin()) die('I said: NO. You are banned for the moment. Go away.');
    if (isset($_POST['password']) && $PAGE->tokenOk($_POST['token']) && ($PAGE->check_auth($_POST['login'], $_POST['password'])))
    {   // Login/password is ok.
        $PAGE->ban_loginOk();

        // If user wants to keep the session cookie even after the browser closes:
        if (!empty($_POST['longlastingsession']))
        {
            $_SESSION['longlastingsession']=31536000;  // (31536000 seconds = 1 year)
            $_SESSION['expires_on']=time()+$_SESSION['longlastingsession'];  // Set session expiration on server-side.
            session_set_cookie_params($_SESSION['longlastingsession'],dirname($_SERVER["SCRIPT_NAME"]).'/'); // Set session cookie expiration on client side
            // Note: Never forget the trailing slash on the cookie path !
            //session_regenerate_id(true);  // Send cookie with new expiration date to browser.
        }
        else // Standard session expiration (=when browser closes)
        {
            session_set_cookie_params(0,dirname($_SERVER["SCRIPT_NAME"]).'/'); // 0 means "When browser closes"
            //session_regenerate_id(true);
        }
        // Optional redirect after login:
        if (isset($_POST['q'])) { 
            header('Location: ?do=addlink&q='.urlencode($_POST['q']).(!empty($_POST['source'])?'&source='.urlencode($_POST['source']):'')); 
            exit; 
        }
        
        header('Location: '.$GLOBALS['ROOT']); exit();
    }
    else
    {
        $PAGE->ban_loginFailed();
        echo '<script language="JavaScript">alert("Wrong login/password.");document.location=\'?do=logout\';</script>'; // Redirect to login screen.
        exit;
    }
}

// Token management for XSRF protection
// Token should be used in any form which acts on data (create,update,delete,import...).
if (!isset($_SESSION['tokens'])) $_SESSION['tokens']=array();  // Token are attached to the session.

// init
// url not yet retrieved
$GLOBALS['done']['d'] = FALSE;

// Get URL to save.
if (!empty($_GET['q']) && $PAGE->isLoggedIn()) {
    //If user is already logged in and source is bookmarklet, display awaiting message
    if (isset($_GET['source']) && $_GET['source']=='bookmarklet') {
    ?><!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8" />
            <title>Respawn – a PHP WebPage Saver</title>
            <link rel="stylesheet" type="text/css" href="style.css"/>
            </head>
        <body>
        <div id="orpx_nav-bar">
            <p>&nbsp;</p>
        </div>
        <div id="wait" style="display:block;">Please wait !<img src="loadinfo.net.gif" alt="wait"/></div>
        <script language="JavaScript">document.location='?do=addlink&q=<?php echo $_GET['q']; ?>&aff=wait';</script>
        </body>
    </html>
    <?php
    exit();
    }
    if (isset($_GET['aff']) && $_GET['aff']=='wait') {
    ?><!DOCTYPE html>
    <html>
        <head>
            <meta charset="utf-8" />
            <title>Respawn – a PHP WebPage Saver</title>
            <link rel="stylesheet" type="text/css" href="style.css"/>
            </head>
        <body>
        <div id="orpx_nav-bar">
            <p>&nbsp;</p>
        </div>
        <div id="wait" style="display:block;">Please wait !<img src="loadinfo.net.gif" alt="wait"/></div>
        <script language="JavaScript">alert("Page was saved.");document.location='?do=logout&source=selfclose';</script>
        </body>
    </html>
    <?php
    }

    $url = htmlspecialchars($_GET['q']);

    if (strpos($url, '://') === false) {
        $url = 'http://'.$url;
    }
    $GLOBALS['url'] = $url;
    $url_p = $Manager->url_parts();

    // retrieve the file main HTML file

    $GLOBALS['main_page_data'] = $Manager->get_external_file($GLOBALS['url'], 6);


    if ($GLOBALS['main_page_data'] === FALSE) {
        echo '<script language="JavaScript">alert("Error retrieving external main page !");document.location=\'?do=addlink\';</script>';
        exit();
    }

    else {
        // crée le nouveau dossier basé sur le TS.
        $new_folder = date('Y-m-d-H-i-s');

        if (!$Manager->creer_dossier($new_folder) === TRUE ) {
            echo '<script language="JavaScript">alert("Error creating data folder !");document.location=\'?do=addlink\';</script>';
            exit();
        }
        else {
            $GLOBALS['target_folder'] = str_replace('//','/',$GLOBALS['data_folder'].'/'.$Manager->dir.$new_folder);
        }
        $liste_css = array();
        // parse le fichier principal à la recherche de données à télécharger
        $files = $Manager->list_retrievable_data($GLOBALS['url'], $GLOBALS['main_page_data']);
        // les récupère et les enregistre.
        //echo '<pre>';print_r($files);die();
        foreach ($files as $i => $file) {
            if ($data = $Manager->get_external_file($file['url_fichier'], 3) and ($data !== FALSE) ) {
                // CSS files need to be parsed aswell
                if ($file['type'] == 'css') {
                    $liste_css[] = $file;
                }
                else {
                    file_put_contents($GLOBALS['target_folder'].'/'.$file['nom_destination'], $data);
                }
            }
        }
        // remplace juste les liens <a href=""> relatifs vers des liens absolus
        $Manager->absolutes_links($GLOBALS['main_page_data']);

        // enregistre le fichier HTML principal
        file_put_contents($GLOBALS['target_folder'].'/'.'index.html', $GLOBALS['main_page_data']);

        // récupère le titre de la page
        // cherche le charset spécifié dans le code HTML.
        // récupère la balise méta tout entière, dans $meta
        preg_match('#<meta .*charset=.*>#Usi', $GLOBALS['main_page_data'], $meta);

        // si la balise a été trouvée, on tente d’isoler l’encodage.
        if (!empty($meta[0])) {
            // récupère juste l’encodage utilisé, dans $enc
            preg_match('#charset="?(.*)"#si', $meta[0], $enc);
            // regarde si le charset a été trouvé, sinon le fixe à UTF-8
            $html_charset = (!empty($enc[1])) ? strtolower($enc[1]) : 'utf-8';
        } else { $html_charset = 'utf-8'; }

        // récupère le titre, dans le tableau $titles, rempli par preg_match()
        preg_match('#<title>(.*)</title>#Usi', $GLOBALS['main_page_data'], $titles);
        if (!empty($titles[1])) {
            $html_title = trim($titles[1]);
            // ré-encode le titre en UTF-8 en fonction de son encodage.
            $title = ($html_charset == 'iso-8859-1') ? utf8_encode($html_title) : $html_title;
        // si pas de titre : on utilise l’URL.
        } else {
            $title = $url;
        }


        // récupère, parse, modifie & enregistre les fichier CSS (et les fichiés liés)
        $n = 0;
        $count = count($liste_css);
        while ( $n < $count ) {
            $i = $n;
            $file = $liste_css[$i];
            if ($data = $Manager->get_external_file($file['url_fichier'], 3) and ($data !== FALSE) ) {
                if (preg_match('#(css|php|txt|html|xml|js)#', $file['url_fichier']) ) {

                    $matches_url = array();
                    preg_match_all('#url\s*\(("|\')?([^\'")]*)(\'|")?\)#i', $data, $matches_url, PREG_SET_ORDER);
                    $matches_url2 = array();
                    preg_match_all('#@import\s*(url\()?["\']?([^\'"\(\);]*)["\']?\)?([^;]*);#i', $data, $matches_url2, PREG_SET_ORDER);

                    $matches_url = array_merge($matches_url2, $matches_url);
            
                    // pour chaque URL/URI
                    foreach ($matches_url as $j => $valuej) {

                        if (preg_match('#^data:#', $matches_url[$j][2])) break; // if BASE64 data, dont download.

                        // get the filenam (basename)
                        $nom_fichier = (preg_match('#^(ht|f)tps?://#', $matches_url[$j][2])) ? pathinfo(parse_url($matches_url[$j][2], PHP_URL_PATH), PATHINFO_BASENAME) : pathinfo($matches_url[$j][2], PATHINFO_BASENAME);

                        // get the URL. For URIs, uses the GLOBALS[url] tu make the URL
                        // the files in CSS are relative to the CSS !
                        if (preg_match('#^https?://#', $matches_url[$j][2])) {
                            $url_fichier = $matches_url[$j][2];
                        }
                        elseif (preg_match('#^/#', $matches_url[$j][2])) {
                            $url_fichier = $url_p['s'].'://'.$url_p['h'].$matches_url[$j][2];
                        }
                        else {
                            $url_fichier = substr($file['url_fichier'], 0, -strlen($file['nom_fich_origine'])).$matches_url[$j][2];
                        }
                        // new rand name, for local storage.
                        $nouveau_nom = $Manager->rand_new_name($nom_fichier);
                        $add = TRUE;

                        // avoids downloading the same file twice. (yes, we re-use the same $retrievable ($files), why not ?)
                        foreach ($files as $key => $item) {
                            if ($item['url_fichier'] == $url_fichier) {
                                $nouveau_nom = $item['nom_destination'];
                                $add = FALSE;
                                break;
                            }
                        }

                        // if we do download, add it to the array.
                        if ($add === TRUE) {
                            $files_n = array(
                                'url_origine' => $matches_url[$j][2],
                                'url_fichier' => $url_fichier,
                                'nom_fich_origine' => $nom_fichier,
                                'nom_destination' => $nouveau_nom
                                );
                            $files[] = $files_n;
                            $liste_css[] = $files_n;
                        }

                        // replace url in CSS $data
                        $data = str_replace($matches_url[$j][2], $nouveau_nom, $data);
                        // echo $nouveau_nom."<br>\n";

                        if (!preg_match('#(css|php|txt|html)#', $file['url_fichier']) ) {
                            if (FALSE !== ($f = $Manager->get_external_file($url_fichier, 3)) ) {
                                file_put_contents($GLOBALS['target_folder'].'/'.$nouveau_nom, $f);
                            }
                        }
                    }
                }

                // don't forget to save data
                file_put_contents($GLOBALS['target_folder'].'/'.$file['nom_destination'], $data);
            }
            $n++;
            $count = count($liste_css);
        }


        // enregistre un fichier d’informations concernant la page (date, url, titre)
        $info  = '';
        $info .= 'URL="'.$GLOBALS['url'].'"'."\n";
        $info .= 'TITLE="'.$title.'"'."\n";
        $info .= 'DATE="'.time().'"'."\n";

        file_put_contents($GLOBALS['target_folder'].'/'.'index.ini', $info);

        // 
        $GLOBALS['done']['d'] = 'ajout';            
        $GLOBALS['done']['lien'] = $GLOBALS['target_folder'].'/';

        // If we are called from the bookmarklet, we must close the session:
        if (isset($_GET['source']) && ($_GET['source']=='bookmarklet' || $_GET['source']=='bookmark')) { 
            echo '<script language="JavaScript">alert("Page was saved.");document.location=\'?do=logout&source=selfclose\';</script>'; exit(); 
        } else {
            echo '<script language="JavaScript">alert("Page was saved.");document.location=\'?do=addlink\';</script>';
            exit();
        }
    }
} else {
    if (isset($_GET['q']) && (isset($_GET['source']) && $_GET['source'] != 'bookmarklet')) {
        header('Location: ?do=addlink');
        exit();
    }
}

if ($GLOBALS['done']['d'] !== FALSE) {
    switch($GLOBALS['done']['d']) {

        case 'ajout' :
            header('Location: index.php?done='.$GLOBALS['done']['d'].'&lien='.urlencode($GLOBALS['url']).'&loclink='.urlencode($GLOBALS['done']['lien']));
            break;

        case 'remove' :
            header('Location: index.php?done='.$GLOBALS['done']['d']);
            break;
    }
    echo '</div>'."\n";
}


# Contenu des 2 listes déroulantes 
$selectionList = array(
    ''=>'For selection...', 
    'move"  onclick="document.getElementById(\'moveIn1\').style.display=\'inline\';document.getElementById(\'moveIn2\').style.display=\'inline\';" title="To move the page, first select the destination folder by changing the folder with the form below'=>'Move', 
    '-'=>'-----', 
    'delete" onclick="document.getElementById(\'moveIn1\').style.display=\'none\';document.getElementById(\'moveIn2\').style.display=\'none\';var answer=window.confirm(\'Sure to remove ?\');if(answer == true) {document.forms[\'form_medias\'].submit();}else {document.getElementById(\'select1\').selectedIndex=document.getElementById(\'select2\').selectedIndex=0;} '=>'Delete'
);

/*
 * Displays main form (page to retrieve)
 *
 */

echo $PAGE->displayPage();
echo '</div>'."\n";
//echo '<pre>'; print_r($_SESSION);echo '</pre>';
echo '<div id="wait">Please wait !<img src="'.$Manager->loadinfo.'" alt="wait"/></div>'."\n";
echo '<div id="panel">'."\n";
if (isset($_GET['done']) and $_GET['done'] !== FALSE) {
    echo '<div id="new-link">'."\n";
    switch($_GET['done']) {
        case 'ajout' :
            echo "\t".'<a target="_blanc" href="'.urldecode($_GET['loclink']).'">Retrieved page</a> - (<a href="'.htmlspecialchars(urldecode($_GET['lien'])).'">orig. page</a>)' ."\n";
            break;

        case 'remove' :
            echo "\t".'Page removed'."\n";
            break;
    }
    echo '</div>'."\n";

}
$path = str_replace('//','/',((empty($Manager->dir) || $Manager->dir == $GLOBALS['data_folder']) ? '/' : '/'.htmlspecialchars($Manager->dir,ENT_QUOTES,'utf-8')));

echo '<p id="ariane">Folder : '.$path.'</p>';
?>

<form action="" method="post" id="form_medias" class="form_medias">
       
        <div id="manager">
            
                <label for="id_newfolder">New Folder&nbsp;:&nbsp;</label>
                <input class="newfolder" id="id_newfolder" type="text" name="newfolder" value="" maxlength="50" size="10" />
                <input class="button new" type="submit" name="btn_newfolder" value="Create" />
                <input type="hidden" name="token" value="<?php echo $PAGE->getToken(); ?>"/>
        </div>

        <div id="browser">
                <label for="folder">Change folder&nbsp;:&nbsp;</label>
                <?php echo $Manager->contentFolder() ?>&nbsp;
                <input class="button submit" type="submit" name="btn_ok" value="OK" />&nbsp;
                <?php if(!empty($Manager->dir) && $Manager->dir != $GLOBALS['data_folder'] && $Manager->dir != '/') : ?>

                <input class="button delete" type="submit" name="btn_delete" onclick="Check=confirm('Delete this folder and its content ?');if(Check==false) return false;" value="Delete" />
                <?php endif; ?>
            
        </div> 
        <div class="files">
            <p style="margin-bottom:15px">
                <?php $PAGE->printSelect('selection[]', $selectionList, '', false, '', 'select1') ?><span id="moveIn1"> in <?php echo $Manager->contentFolder('1') ?></span>
                <input class="button submit" type="submit" name="btn_action" value="OK" />
            </p>
            <p>Check all : <input type="checkbox" onclick="checkAll(this.form, 'idFile[]')" /></p>
            <?php 
$dirToScan = str_replace('//', '/', $GLOBALS['data_folder'].((empty($Manager->dir) || $Manager->dir != $GLOBALS['data_folder']) ? '/' : '').$Manager->dir);
$liste_pages = scandir($dirToScan);


$j = FALSE;
if ( ($nb = count($liste_pages)) != 0 ) {
    echo '<ul id="liste-pages-sauvees">'."\n";
    for ($i = 0 ; $i < $nb ; $i++) {

        $folder = str_replace('//', '/', $GLOBALS['data_folder'].$Manager->dir.'/'.$liste_pages[$i]);

        // dont list '.' and '..' folders.
        if (is_dir($folder) and ($liste_pages[$i] != '.') and ($liste_pages[$i] != '..') and ($liste_pages[$i] != 'config')) {
            // each folder should contain such a file "index.ini".
            $ini_file = $folder.'/index.ini';
            if ( is_file($ini_file) and is_readable($ini_file) ) {
                $infos = parse_ini_file($ini_file);
            } else {
                $infos = FALSE;
            }
            if (FALSE !== $infos) {
                $j = TRUE;
                $titre = $infos['TITLE']; $url = $infos['URL']; $date = date('d/m/Y, H:i:s', $infos['DATE']);

            echo "\t".'<li>';echo ( $PAGE->isLoggedIn() ? '<input type="checkbox" name="idFile[]" value="'.$liste_pages[$i].'" /><a onclick="return window.confirm(\'Sure to remove?\')" href="?suppr='.$folder.'&token='.$PAGE->getToken().'"><img src="'.$Manager->trash.'" alt="suppr" title="suppr" width="13" height="19"/></a> – ' : ''); echo '<a href="'.$url.'"><img src="'.$Manager->location.'" alt="orig" title="orig" width="13" height="19"/></a> – '.$date.' – <a href="'.$folder.'">'.$titre.'</a></li>'."\n";
            } else {
                $titre = 'titre'; $url = '#'; $date = 'date inconnue';
            }
        } 
    }
    if ($j === FALSE) {
        echo "\t".'<li><img src="'.$Manager->info.'" alt="info" title="info" width="13" height="19"/> - No record</li>'."\n";
    }
    
    echo '</ul>'."\n";
}
            ?>
            <p>
                <?php $PAGE->printSelect('selection[]', $selectionList , '', false, '', 'select2') ?><span id="moveIn2"> in <?php echo $Manager->contentFolder('2') ?></span>
                <input class="button submit" type="submit" name="btn_action" value="OK" />
                <input type="hidden" name="sort" value="" />
            </p>
        </div>
    </form>
    </div><!-- End panel -->
    <!-- SCRIPT FROM PLUXML -->
    <script type="text/javascript">
    <!-- 
        function checkAll(inputs, field) {
            for(var i = 0; i < inputs.elements.length; i++) {
                if(inputs[i].type == "checkbox" && inputs[i].name==field) {
                    inputs[i].checked = !inputs[i].checked ;
                }
            }
        }
    -->
    </script>
    </body>
</html>
