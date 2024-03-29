<?php 
namespace Lotus\Framework;

use Lotus\Framework\Route as Route;

class Routes
{

    /**
     * The route collection instance.
     */
    private $routeCollections = array();

    /**
     * Register a GET route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */

    public function get($route, $handler)
    {
        $this->register('GET', $route, $handler);
    }

    /**
     * Register a POST route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    public function post($route, $handler)
    {
        $this->register('POST', $route, $handler);
    }

    /**
     * Register a PUT route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    public function put($route, $handler)
    {
        $this->register('PUT', $route, $handler);
    }

    /**
     * Register a DELETE route with the router.
     *
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    public function delete($route, $handler)
    {
        $this->register('DELETE', $route, $handler);
    }

    /**
     * Register a route that handles any request method.
     *
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    public function any($route, $handler)
    {
        $this->register(array('GET','POST','PUT','DELETE'), $route, $handler);
    }

    /**
     * Register a HTTPS route with the router.
     *
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    public function secure($method, $route, $handler)
    {
        // stop when not secure
        // @TODO Secure

        // if (!Router::secure())
            // return;
        
        // static::register($method, $route, $handler);
    }

    /**
     * Register a route with the router.
     * 
     * @param  string        $method
     * @param  string|array  $route
     * @param  mixed         $handler
     * @return void
     */
    protected function register($method, $route, $handler)
    {
        // Protect / route being used by lotus framework
        if($route=="/")
            return;

        // If the developer is registering multiple request methods to handle
        // the URI, we'll spin through each method and register the route
        // for each of them along with each URI and handler.

        // TODO : Only put the same method route to route array

        if (is_array($method))
        {
            foreach ($method as $http)
            {

                $routeCollection = new Route($http,$route,$handler);

                //Add this route to the $routeCollections params
                array_push($this->routeCollections,$routeCollection);

            }
            return;
        }
        
        // Create routeCollection from params
        $routeCollection = new Route($method,$route,$handler);

        //Add this route to the $routeCollections params
        array_push($this->routeCollections,$routeCollection);
    }

    /*
     * Get all registered route
     */
    public function getRouteCollections()
    {
        return $this->routeCollections;
    }

}