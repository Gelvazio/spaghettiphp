<?php
/**
 *  A classe View � respons�vel por extrair o conte�do enviado pelo controlador
 *  e associ�-lo ao view e layout correspondentes, fazendo tamb�m a inclus�o
 *  dos helpers necess�rios.
 *
 *  Licensed under The MIT License.
 *  Redistributions of files must retain the above copyright notice.
 *  
 *  @package Spaghetti
 *  @subpackage Spaghetti.Core.View
 *  @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */
    
class View extends Object {
    public $helpers = array("Html");
    public $loaded_helpers = array();
    public $controller;
    public $action;
    public $extension;
    public $layout;
    public $page_title;
    public $auto_layout = true;
    public $view_data = array();
    public function __construct(&$controller = null) {
        if($controller):
            $this->controller = $controller->params("controller");
            $this->action = $controller->params("action");
            $this->extension = $controller->params("extension");
            $this->page_title = $controller->page_title;
            $this->layout = $controller->layout;
            $this->auto_layout = $controller->auto_layout;
        endif;
    }
    /**
     * View::load_helpers() faz a inclus�o dos helpers necess�rios
     * solicitados anteriormente pelo controlador e que agora ser�o usados
     * pela view.
     *
     * @return array Array de objetos das classes dos helpers
     */
    public function load_helpers() {
        foreach($this->helpers as $helper):
            $class = "{$helper}Helper";
            $this->loaded_helpers[Inflector::underscore($helper)] = ClassRegistry::init($class, "Helper");
        endforeach;
        return $this->loaded_helpers;
    }
    /**
     * O m�todo View::render_view() recebe o resultado do processamento do
     * controlador atual e renderiza a view correspondente, retornando um HTML
     * est�tico do conte�do solicitado. Chama tamb�m o m�todo respons�vel por
     * extrair os helpers e associ�-los ao view.
     *
     * @param string $filename Nome do arquivo de view
     * @param array $extract_vars Vari�veis a serem passadas para a view
     * @return string HTML da view renderizada
     */
    public function render_view($filename = null, $extract_vars = array()) {
        if(!is_string($filename)):
            return false;
        endif;
        if(empty($this->loaded_helpers) && !empty($this->helpers)):
            $this->load_helpers();
        endif;
        $extract_vars = is_array($extract_vars) ? array_merge($extract_vars, $this->loaded_helpers) : $this->loaded_helpers;
        extract($extract_vars, EXTR_SKIP);
        ob_start();
        include $filename;
        $out = ob_get_clean();
        return $out;
    }
    /**
     * View::render() � respons�vel por receber uma a��o, um controlador e
     * um layout e fazer as inclus�es necess�rias para a renderiza��o da tela,
     * chamando outros m�todos para renderizar o view e o layout.
     *
     * @param string $action Nome da a��o a ser chamada
     * @param string $layout Nome do arquivo de layout
     * @return string HTML final da renderiza��o.
     */
    public function render($action = null, $layout = null) {
        if($action === null):
            $action = "{$this->controller}/{$this->action}";
            $ext = "p{$this->extension}";
        else:
            $filename = preg_split("/\./", $action);
            $action = $filename[0];
            $ext = $filename[1] ? $filename[1] : "phtm";
        endif;
        $layout = $layout === null ? "{$this->layout}.{$ext}" : $layout;
        $filename = Spaghetti::import("View", $action, $ext, true);
        if($filename):
            $out = $this->render_view($filename, $this->view_data);
            if($layout && $this->auto_layout):
                $out = $this->render_layout($out, $layout);
            endif;
            return $out;
        else:
            $this->error("missingView", array("controller" => $this->controller, "view" => $action, "extension" => $ext));
            return false;
        endif;
    }
    /**
     * O m�todo View::render_layout() faz o buffer e a renderiza��o do layout
     * requisitado, incluindo a view correspondente a requisi��o atual e passando
     * as vari�veis definidas no controlador. Retorna o HTML processado, sem PHP.
     *
     * @param string $content Conte�do a ser passado para o layout
     * @param string layout Nome do arquivo de layout
     * @return string HTML do layout renderizado
     */
    public function render_layout($content = null, $layout = null) {
        if($layout === null):
            $layout = $this->layout;
            $ext = "p{$this->extension}";
        else:
            $filename = preg_split("/\./", $layout);
            $layout = $filename[0];
            $ext = $filename[1] ? $filename[1] : "phtm";
        endif;
        $filename = Spaghetti::import("Layout", $layout, $ext, true);
        $data = array_merge(array(
            "content_for_layout" => $content,
            "page_title" => $this->page_title,
        ), $this->view_data);
        if($filename):
            $out = $this->render_view($filename, $data);
            return $out;
        else:
            $this->error("missingLayout", array("layout" => $layout, "extension" => $ext));
            return false;
        endif;
    }
    /**
     * O m�todo View::element() retorna o buffer do carregamento de um elemento,
     * que s�o arquivos de views que s�o repetidos muitas vezes, e podem assim
     * estar em um arquivo s�. Isto � bastante �til para trechos repetidos de
     * c�digo PHTML, para que nem seja necess�rio criar um novo layout nem repetir
     * este trecho a cada arquivo onde seja necess�rio.
     *
     * @param string $element Nome do arquivo elemento
     * @param array $params Par�metros opcionais a serem passados para o elemento
     * @return string Buffer do arquivo solicitado
     */
    public function element($element = null, $params = array()) {
        $ext = $this->extension ? "p{$this->extension}" : "phtm";
        return $this->render_view(Spaghetti::import("View", "elements/_{$element}", $ext, true), $params);
    }
    /**
     * View::set() � o m�todo que grava as vari�veis definidas no
     * controlador que ser�o passadas para o view em seguida.
     *
     * @param mixed $var String com nome da vari�vel ou array de vari�veis e valores
     * @param mixed $content Valor da vari�vel, � aceito qualquer tipo de conte�do
     * @return mixed Retorna o conte�do da vari�vel gravada
     */
    public function set($var = null, $content = null) {
        if(is_array($var)):
            foreach($var as $key => $value):
                $this->set($key, $value);
            endforeach;
        elseif($var !== null):
            $this->view_data[$var] = $content;
            return $this->view_data[$var];
        endif;
        return false;
    }
}
?>