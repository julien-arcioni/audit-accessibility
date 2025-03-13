<?php

/**
 * Gère les hooks de l'extension
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Loader {

    /**
     * Le tableau des actions enregistrées avec WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    Les actions enregistrées avec WordPress pour exécution
     */
    protected $actions;

    /**
     * Le tableau des filtres enregistrés avec WordPress
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    Les filtres enregistrés avec WordPress pour exécution
     */
    protected $filters;

    /**
     * Initialise les collections utilisées pour maintenir les actions et filtres
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Ajoute une nouvelle action au tableau des actions à enregistrer avec WordPress
     *
     * @since    1.0.0
     * @param    string               $hook             Le nom de l'action WordPress à laquelle se connecter
     * @param    object               $component        Une référence à l'instance de l'objet sur lequel l'action est définie
     * @param    string               $callback         Le nom de la méthode de callback de fonction
     * @param    int                  $priority         Priorité facultative. Par défaut 10
     * @param    int                  $accepted_args    Nombre facultatif d'arguments acceptés. Par défaut 1
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Ajoute un nouveau filtre au tableau des filtres à enregistrer avec WordPress
     *
     * @since    1.0.0
     * @param    string               $hook             Le nom du filtre WordPress auquel se connecter
     * @param    object               $component        Une référence à l'instance de l'objet sur lequel le filtre est défini
     * @param    string               $callback         Le nom de la méthode de callback de fonction
     * @param    int                  $priority         Priorité facultative. Par défaut 10
     * @param    int                  $accepted_args    Nombre facultatif d'arguments acceptés. Par défaut 1
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Méthode utilitaire qui est utilisée pour enregistrer les filtres et actions dans une seule
     * collection
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            Collection de hooks (c'est-à-dire actions ou filtres)
     * @param    string               $hook             Le nom du hook WordPress
     * @param    object               $component        Une référence à l'instance de l'objet
     * @param    string               $callback         Le nom de la méthode de callback de fonction
     * @param    int                  $priority         La priorité pour laquelle le hook devrait être exécuté
     * @param    int                  $accepted_args    Le nombre d'arguments que le hook accepte
     * @return   array                                  La collection de hooks mise à jour
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Enregistre les filtres et actions auprès de WordPress
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
} 