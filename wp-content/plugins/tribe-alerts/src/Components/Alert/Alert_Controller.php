<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert;

use Tribe\Alert_Scoped\League\Plates\Engine;
use Tribe\Alert\Components\Controller;
use Tribe\Alert_Scoped\Tribe\Libs\Utils\Markup_Utils;
class Alert_Controller extends Controller
{
    public const VIEW = 'alert';
    public const COLOR_THEME_CLASS = 'tribe-alerts__has-theme';
    protected \Tribe\Alert\Components\Alert\Alert_Model $model;
    public function __construct(Engine $view, \Tribe\Alert\Components\Alert\Alert_Model $model)
    {
        parent::__construct($view);
        $this->model = $model;
    }
    public function render() : void
    {
        $alert = $this->model->get_data();
        if (!$alert->id) {
            return;
        }
        echo $this->view->render(self::VIEW, ['dto' => $alert, 'classes' => $this->get_alert_classes($alert), 'link_attributes' => $this->get_link_attributes($alert)]);
    }
    protected function get_alert_classes(\Tribe\Alert\Components\Alert\Alert_Dto $alert) : string
    {
        $classes = ['tribe-alerts'];
        if ($alert->color_class) {
            $classes[] = self::COLOR_THEME_CLASS;
            $classes[] = $alert->color_class;
        }
        return Markup_Utils::class_attribute($classes);
    }
    protected function get_link_attributes(\Tribe\Alert\Components\Alert\Alert_Dto $alert) : string
    {
        return Markup_Utils::concat_attrs(\array_filter(['target' => $alert->cta->target, 'aria-label' => $alert->cta->add_aria_label ? $alert->cta->aria_label : '']));
    }
}
