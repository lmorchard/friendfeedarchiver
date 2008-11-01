<?php
/**
 * Custom controller subclass used throughout application
 *
 * @package    NotionalBucket
 * @author     l.m.orchard@pobox.com
 */
set_include_path(APPPATH . 'vendor' . PATH_SEPARATOR . get_include_path());

class Controller extends Controller_Core {

    // Wrapper layout for current view
    protected $layout = 'layout';

    // Wrapped view for current method
    protected $view = FALSE;

    // Automatically render the layout?
    protected $auto_render = FALSE;

    // Variables for templates
    protected $view_data;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Start with empty set of view vars.
        $this->view_data = array(
            'controller' => $this
        );

        // Display the template immediately after the controller method
        Event::add('system.post_controller', array($this, '_display'));
    }

    /**
     * Set the state of auto rendering at the end of controller method.
     *
     * @param  boolean whether or not to autorender
     * @return object
     */
    public function setAutoRender($state=TRUE)
    {
        $this->auto_render = $state;
        return $this;
    }

    /**
     * Set the name of the wrapper layout to use at rendering time.
     *
     * @param  string name of the layout wrapper view.
     * @return object
     */
    public function setLayout($name)
    {
        $this->layout = $name;
        return $this;
    }

    /**
     * Set the name of the wrapped view to use at rendering time.
     *
     * @param  string name of the view to use during rendering.
     * @return object
     */
    public function setView($name)
    {
        // Prepend path with the controller name, if not already a path.
        if (strpos($name, '/') === FALSE)
            $name = Router::$controller . '/' . $name;
        $this->view = $name;
        return $this;
    }

    /**
     * Sets one or more view variables for layout wrapper and contained view
     *
     * @param  string|array  name of variable or an array of variables
     * @param  mixed         value of the named variable
     * @return object
     */
    public function setViewData($name, $value=NULL)
    {
        if (func_num_args() === 1 AND is_array($name)) {
            // Given an array of variables, merge them into the working set.
            $this->view_data = array_merge($this->view_data, $name);
        } else {
            // Set one variable in the working set.
            $this->view_data[$name] = $value;
        }
        return $this;
    }

    /**
     * Get one or all view variables.
     *
     * @param string  name of the variable to get, or none for all
     * @return mixed
     */
    public function getViewData($name=FALSE, $default=NULL)
    {
        if ($name) {
            return isset($this->view_data[$name]) ?
                $this->view_data[$name] : $default;
        } else {
            return $this->view_data;
        }
    }

    /**
     * Remap routed controller method based on HTTP method and any other 
     * relevant details.  (ie. content-type?)
     *
     * @param
     */
    public function _remap($method, $args)
    {
        // Tweak routed method to include HTTP request method, if named 
        // controller method exists.
        $request_method = strtoupper( $_SERVER['REQUEST_METHOD'] );
        if (method_exists($this, $method.'_'.$request_method) ) {
            $method .= '_' . $request_method;
        }

        // Update the router with tweaked method.
        Router::$method = $method;

        // Set the default view name to controller/method name.
        $this->setView(Router::$controller . '/' . $method);

        // Finally, call the appropriate controller method.
        call_user_func_array(array($this,$method),$args);
    }

    /**
     * Render a template wrapped in the global layout.
     */
    public function _display()
    {
        // Do nothing if auto_render is false at this point.
        if ($this->auto_render) {

            // If there's a view set, render it first into .
            if ($this->view) {
                // $view = new View($this->view, $this->getViewData());
                $view = new View($this->view);
                $view->set_global($this->getViewData());
                $this->setViewData('content', $view->render());
            }

            if ($this->layout) {
                // Finally, render the layout wrapper to the browser.
                $layout = new View($this->layout);
                $layout->set_global($this->getViewData());
                $layout->render(TRUE);
            } else {
                // No layout wrapper, so try outputting the rendered view.
                echo $this->getViewData('content', '');
            }

        }

    }

    /**
     * Construct a JSON response, with optional callback parameter.
     * 
     * @param mixed Data to be output as JSON.
     */
    public function renderJSON($data)
    {
        // Accept a ?callback parameter.
        if (!isset($_GET['callback'])) {
            $callback = FALSE;
        } else {
            // Pass the callback parameter through a whitelist to help prevent some 
            // potential XSS issues.
            $callback = preg_replace(
                '/[^0-9a-zA-Z\(\)\[\]\,\.\_\-\+\=\/\|\\\~\?\!\#\$\^\*\: \'\"]/', '', 
                $_GET['callback']
            );
        }

        // Finally, spit out the JSON, including the callback if supplied.
        $this->setAutoRender(FALSE);
        header('Content-Type: application/json; charset=utf-8');
        if ($callback) echo "$callback(";
        echo json_encode($data);
        if ($callback) echo ")";
    }

}
