<?php

namespace Goteo\Model {

    use Goteo\Library\Text,
        Goteo\Library\FileHandler\File,
        Goteo\Library\MImage,
        Goteo\Library\Cache;

    class Image extends \Goteo\Core\Model {

        public
			$id,
            $name,
            $type,
            $tmp,
            $error,
            $size,
            $dir_originals = 'images/', //directorio archivos originales (relativo a data/ o al bucket s3)
            $dir_cache = 'cache/', //directorio archivos cache (relativo a data/ o al bucket s3 en cuanto se implemente)
            $newstyle = false; // new style es no usar tabla image

        private $fp;

        public static $types = array('project', 'post', 'glossary', 'info');

        // decopilatorio con los tamaños habituales para imágenes de cada entidad
        // este es el tamaño que se usa en la página de gestión de la entidad (tabla)
        // sale de buscat getLink() en toda la aplicación
        public static $sizes = array(
            'user-avatar' => '56x56c', // cabecera en la página de proyecto y perfil (hay más tamaños)
            'banner' => '700x156c',
            'call-logo' => '250x124c',
            'call-image' => '',
            'call-backimage' => '', // sistintos tamaños segun dispositivo
            'call_banner' => '',
            'call_sponsor' => '',
            'project' => '580x580',
            'post' => '500x285',
            'glossary' => '500x285',
            'info' => '500x285',
            'story' => '940x385c',
            'sponsor' => '150x85'
        );

        /**
         * Constructor.
         *
         * @param type array	$file	Array $_FILES.
         */
        public function __construct ($file = null) {

            if(is_array($file)) {
                $this->name = $file['name'];
                $this->type = $file['type'];
                $this->tmp = $file['tmp_name'];
                $this->error = $file['error'];
                $this->size = $file['size'];
            }
            elseif(is_string($file)) {
				$this->name = basename($file);
				$this->tmp = $file;
			}

            $this->fp = File::factory(array('bucket' => AWS_S3_BUCKET_STATIC));
            $this->fp->setPath($this->dir_originals);
        }

        /**
         * Sobrecarga de métodos 'getter'.
         *
         * @param type string $name
         * @return type mixed
         */
        public function __get ($name) {
            if($name == "content") {
	            return $this->getContent();
	        }
            return $this->$name;
        }

        /**
         * Retorna URL del archivo o ruta absluta si es local
         */
        public function url( $path = null) {
            if($path === null) $path = $this->dir_originals . $this->name;
            //url del archivo o ruta absoluta si es local

            // @TODO: El uso de $fp debe ser independiente de su implementación
            if($this->fp instanceof LocalFile) {
                $url = SRC_URL . '/data/' . $path;
                die($url);
            } else {
                $url = SRC_URL . '/' . $path;
            }

            return $url;
        }

        /**
         * (non-PHPdoc)
         * @see Goteo\Core.Model::save()
         *
         * FALTA!!!
         */
        public function save(&$errors = array()) {
            if($this->validate($errors)) {
                $this->original_name = $this->name;
                //nombre seguro
                $this->name = $this->fp->get_save_name($this->name);

                if(!empty($this->name)) {
                    $data[':name'] = $this->name;
                }

                if(!empty($this->type)) {
                    $data[':type'] = $this->type;
                }

                if(!empty($this->size)) {
                    $data[':size'] = $this->size;
                }

                try {

                    if(!empty($this->tmp)) {
                        $uploaded = $this->fp->upload($this->tmp, $this->name);

                        //@FIXME falta checkear que la imagen se ha subido correctamente
                        if (!$uploaded) {
                            $errors[] = 'fp->upload : <br />'.$this->tmp.' <br />dir: '.$this->dir_originals.' <br />file name: '.$this->name . '<br />from: '.$this->original_name;
                            return false;
                        }
                    }
                    else {
                        $errors[] = Text::get('image-upload-fail');
                        return false;
                    }

                    if ($this->newstyle) {

                        // no guardamos en tabla, id es el nombre del archivo
                        $this->id = $this->name;

                    } else {

                        // @FIXME esto se podrá quitar cuando todas las entidades image estén modificadas


                        // Construye SQL.
                        $query = "REPLACE INTO image (";
                        foreach($data AS $key => $row) {
                            $query .= substr($key, 1) . ", ";
                        }
                        $query = substr($query, 0, -2) . ") VALUES (";
                        foreach($data AS $key => $row) {
                            $query .= $key . ", ";
                        }
                        $query = substr($query, 0, -2) . ")";
                        // Ejecuta SQL.
                        $result = self::query($query, $data);
                        if(empty($this->id)) $this->id = self::insertId();

                    }

                    return true;

            	} catch(\PDOException $e) {
                    $errors[] = "No se ha podido guardar la imagen: " . $e->getMessage();
                    return false;
    			}
            }
            return false;
		}

		/**
		* Returns a secure name to store in file system, if the generated filename exists returns a non-existing one
		* @param $name original name to be changed-sanitized
		* @param $dir if specified, generated name will be changed if exists in that dir
        * Esto ya lo hace la clase File con get_save_name
        */
        /*
		public static function check_filename($name='',$dir=null){
			$name = preg_replace("/[^a-z0-9_~\.-]+/","-",strtolower(self::idealiza($name, true)));
			if(is_dir($dir)) {
				while ( file_exists ( "$dir/$name" )) {
					$name = preg_replace ( "/^(.+?)(_?)(\d*)(\.[^.]+)?$/e", "'\$1_'.(\$3+1).'\$4'", $name );
				}
			}
			return $name;
		}
		*/

		/**
		 * (non-PHPdoc)
		 * @see Goteo\Core.Model::validate()
		 */
		public function validate(&$errors = array()) {

			if(empty($this->name)) {
                $errors['image'] = Text::get('error-image-name');
            }

            // checkeo de errores de $_FILES
            if($this->error !== UPLOAD_ERR_OK) {
                switch($this->error) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errors['image'] = Text::get('error-image-size-too-large');
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errors['image'] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errors['image'] = 'The uploaded file was only partially uploaded';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        if (isset($_POST['upload']))
                            $errors['image'] = 'No file was uploaded';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errors['image'] = 'Missing a temporary folder';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errors['image'] = 'Failed to write file to disk';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errors['image'] = 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions';
                        break;
                }
                return false;
            }

            if(!empty($this->type)) {
                $allowed_types = array(
                    'image/gif',
                    'image/jpeg',
                    'image/png',
                    'image/svg+xml',
                );
                if(!in_array($this->type, $allowed_types)) {
                    $errors['image'] = Text::get('error-image-type-not-allowed');
                }
            }
            else {
                $errors['image'] = Text::get('error-image-type');
            }

            if(empty($this->tmp) || $this->tmp == "none") {
                $errors['image'] = Text::get('error-image-tmp');
            }

            if(empty($this->size)) {
                $errors['image'] = Text::get('error-image-size');
            }

            return empty($errors);
		}

		/**
		 * Imagen.
		 *
		 * @param type int	$id
		 * @return type object	Image
		 */
	    static public function get ($id, $debug = false) {

            if ($debug) echo "Request image $id<br />";
            try {

                if (empty($id))
                    $id = 1;

                // imagenes especiales
                switch ($id) {
                    case '1':
                        $id = 'la_gota.png'; // imagen por defecto en toda la aplicación
                        break;
                    case '2':
                        $id = 'la_gota-wof.png'; // imagen por defecto en el wall of friends
                        break;
                }

                if (is_numeric($id)) {

                    $query = static::query("
                    SELECT
                        id,
                        name,
                        type,
                        size
                    FROM image
                    WHERE id = :id
                    ", array(':id' => $id));
                    $image = $query->fetchObject(__CLASS__);

                    if ($debug) echo "Numeric, from table: ".\trace($image);


                } else {
                    $image = new Image;
                    $image->name = $id;
                    $image->id = $id;
                    $image->hash = md5($id);

                    if ($debug) echo "Not numeric, from name: <br />";
                    if ($debug) echo \trace($image);
                    if ($debug) echo $image->getLink(150, 85);


                }

                if ($debug) die;

                    return $image;
            } catch(\PDOException $e) {
                return false;
            }
		}

        /**
         * Galeria de imágenes de un usuario / proyecto
         *
         * @param  varchar(50)  $id    user id |project id
         * @param  string       $which    'user'|'project'
         * @return mixed        false|array de instancias de Image
         */
        public static function getAll ($id, $which) {

            if (!\is_string($which) || !\in_array($which, self::$types)) {
                return false;
            }

            $gallery = array();

            try {
                $sql = "SELECT image FROM {$which}_image WHERE {$which} = ?";
                $sql .= ($which == 'project') ? " ORDER BY section ASC, `order` ASC, image DESC" : " ORDER BY image ASC";
                $query = self::query($sql, array($id));
                foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $image) {
                    $gallery[] = self::get($image['image']);
                }

                return $gallery;
            } catch(\PDOException $e) {
                return false;
            }

        }

        /**
         * Quita una imagen de la tabla de relaciones y de la tabla de imagenes
         *
         * @param  string       $which    'project', 'post', 'glossary', 'info'
         * @return bool        true|false
         *
         */
        public function remove(&$errors = array(), $which = null) {

            // no borramos nunca la imagen de la gota
            if ($this->id == 'la_gota.png') return false;

            $this->fp->delete($this->id);
            try {
                if (\is_string($which) && \in_array($which, self::$types)) {
                    $sql = "DELETE FROM {$which}_image WHERE image = ?";
                    $query = self::query($sql, array($this->id));
                }

                //esborra de disk
                $this->fp->delete($this->id);

                return true;
            } catch(\PDOException $e) {
                $errors[] = $e->getMessage();
                return false;
            }
        }


		/**
		 * Para montar la url de una imagen (porque las url con parametros no se cachean bien)
		 *  - Si el thumb está creado, montamos la url de /data/cache
         *  - Sino, monamos la url de /image/
         *
		 * @param type int $id
		 * @param type int $width
		 * @param type int $height
		 * @param type int $crop
		 * @return type string
		 */
		public function getLink ($width = 'auto', $height = 'auto', $crop = false) {

            $tc = ($crop ? 'c' : '');
            $cache = $width . 'x' . $height . $tc .'/' .$this->name;


            $c = new Cache($this->dir_cache);

            if($c->get_file($cache)) {
                return SITE_URL . '/data/' . $this->dir_cache . $cache;
            } else {

                if (is_numeric($this->id)) {
                    // controlador antigo por id
                    return SITE_URL . '/image/' . $this->id .'/'. $width . '/' . $height . '/' . $crop;
                } else {
                    // controlador nuevo por nombre de archivo
                    return SITE_URL . '/img/' . $cache;
                }

            }

		}

		/**
		 * Muestra la imagen en pantalla.
		 * @param type int	$width
		 * @param type int	$height
		 */
        public function display ($width, $height, $crop) {

            $cache = $width."x$height" . ($crop ? 'c' : '') . "/" . $this->name;
            $c = new Cache($this->dir_cache);
            ignore_user_abort(true);
            //comprueba si existe el archivo en cache
            if($c->get_file($cache)) {
                //si existe, servimos el fichero inmediatamante (via redireccion http)
                //PERO continuamos la ejecuciÃ³n del script para recrear el cache si estÃ¡ expirado
                ob_end_clean();
                header('Connection: close', true);

                $url_cache = SITE_URL . '/data/' . $this->dir_cache . $cache;

                self::stream($url_cache, false);
                //close connection with browser
                ob_end_flush();
                flush();
                //check if file is newer
                if(!$c->expired($cache, $this->fp->mtime($this->name))) {
                    exit;
                }
                //continue to force rebuild cache

            }
            //si no existe o es nuevo, creamos el archivo
            if (defined("FILE_HANDLER") && FILE_HANDLER == 's3' && defined("AWS_SECRET") && defined("AWS_KEY")) {
                $url_original = SRC_URL . '/images/' . $this->name;
            }
            else {
                $url_original = dirname(__DIR__) . '/data/images/' . $this->name;
            }

            $im = new MImage($url_original);
            $im->fallback('auto');
            $im->proportional($crop ? 1 : 2);
            $im->quality(98);

            $im->resize($width, $height);

            //guardar a cache si no hay errores
            if(!$im->has_errors()) {
                //guardar un archivo temporal y subir
                $tmp = tempnam(sys_get_temp_dir(), 'goteo-img');
                $im->save($tmp);
                //subir el archivo a cache
                $c->put_file($tmp, $cache);
                unlink($tmp);
            }

            ignore_user_abort(false);

            //stream del archivo creado y muerte del script
            $im->flush();
		}

        /**
         * Passthru a file with content-type, name
         * @param  [type] $file [description]
         * @return [type]       [description]
         */
        static function stream($file, $exit = true) {
            //redirection if is http stream
            if(substr($file, 0 , 7) == 'http://' || substr($file, 0 , 8) == 'https://') {
                header("Location: $file");
            }
            else {
                list($width, $height, $type, $attr) = @getimagesize( $file );
                if(!$type && function_exists( 'exif_imagetype' ) ) {
                    $type = exif_imagetype($file);
                }
                if($type) {
                     $type = image_type_to_mime_type($type);
                }
                else {
                    $type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if($type == 'jpg') $type = "jpeg";
                    if(!in_array($type, array('jpeg', 'png', 'gif'))) die("file $type not image!");
                    $type = "image/$type";
                }

                header("Content-type: " . $type);
                header('Content-Disposition: inline; filename="' . str_replace("'", "\'", basename($file)) . '"');
                header("Content-Length: " . @filesize($file));
                readfile($file);
            }
            if($exit) exit;
        }

        private function getContent () {
            return file_get_contents($this->name);
    	}

        /**
         * Reemplaza la extensión de la imagen.
         *
         * @param type string	$src
         * @param type string	$new
         * @return type string
         */
    	static private function replace_extension($src, $new) {
    	    $pathinfo = pathinfo($src);
    	    unset($pathinfo["basename"]);
    	    unset($pathinfo["extension"]);
    	    return implode(DIRECTORY_SEPARATOR, $pathinfo) . '.' . $new;
    	}

	}

}
