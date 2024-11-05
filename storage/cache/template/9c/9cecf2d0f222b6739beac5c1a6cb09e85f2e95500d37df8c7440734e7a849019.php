<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* default/template/extension/payment/nimbbl.twig */
class __TwigTemplate_34a2b47f4ce34b5330592ae13828fe65084bc667de8d25e2198e91022ee16003 extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        if ( !twig_test_empty(($context["error"] ?? null))) {
            // line 2
            echo "    ";
            echo ($context["error"] ?? null);
            echo "
";
        } else {
            // line 4
            echo "    ";
            echo ($context["data"] ?? null);
            echo "
";
        }
        // line 6
        echo "\t";
    }

    public function getTemplateName()
    {
        return "default/template/extension/payment/nimbbl.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  51 => 6,  45 => 4,  39 => 2,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "default/template/extension/payment/nimbbl.twig", "");
    }
}
