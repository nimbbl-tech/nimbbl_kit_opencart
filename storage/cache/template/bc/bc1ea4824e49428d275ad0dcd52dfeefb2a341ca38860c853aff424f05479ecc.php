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

/* extension/payment/nimbbl.twig */
class __TwigTemplate_ecfa011705043da78f8cc7df26a00cc5de0dcc3da9e6f5392c211b8d6d6b2bb3 extends \Twig\Template
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
        echo ($context["header"] ?? null);
        echo ($context["column_left"] ?? null);
        echo "
<div id=\"content\">
  <div class=\"page-header\">
  <div class=\"container-fluid\">
  <div class=\"pull-right\">
         <button type=\"submit\" form=\"form-payment\" data-toggle=\"tooltip\" title=\"";
        // line 6
        echo ($context["button_save"] ?? null);
        echo "\" class=\"btn btn-primary\"><i class=\"fa fa-save\"></i></button>
        <a href=\"";
        // line 7
        echo ($context["cancel"] ?? null);
        echo "\" data-toggle=\"tooltip\" title=\"";
        echo ($context["button_cancel"] ?? null);
        echo "\" class=\"btn btn-default\"><i class=\"fa fa-reply\"></i></a></div>
      <h1>";
        // line 8
        echo ($context["heading_title"] ?? null);
        echo "</h1>
 
  <ul class=\"breadcrumb\">
    ";
        // line 11
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["breadcrumbs"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["breadcrumb"]) {
            // line 12
            echo "        <li><a href=\"";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "href", [], "any", false, false, false, 12);
            echo "\">";
            echo twig_get_attribute($this->env, $this->source, $context["breadcrumb"], "text", [], "any", false, false, false, 12);
            echo "</a></li>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['breadcrumb'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 14
        echo "  </ul>
  </div>
  </div>
  <div class=\"container-fluid\">
    ";
        // line 18
        if (($context["error_warning"] ?? null)) {
            // line 19
            echo "    <div class=\"alert alert-danger alert-dismissible\"><i class=\"fa fa-exclamation-circle\"></i> ";
            echo ($context["error_warning"] ?? null);
            echo "
      <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
    </div>
    ";
        }
        // line 23
        echo "  <div class=\"panel panel-default\">
      <div class=\"panel-heading\">
        <h3 class=\"panel-title\"><i class=\"fa fa-pencil\"></i>";
        // line 25
        echo ($context["text_edit"] ?? null);
        echo "</h3>
  </div>
  <form action=\"";
        // line 27
        echo ($context["action"] ?? null);
        echo "\" method=\"post\" enctype=\"multipart/form-data\" name=\"form-nimbbl\" id=\"form-nimbbl\" class=\"form-horizontal\">
 <div class=\"box\">
  <!--// Nimbbl Start //-->
  <div class=\"heading\">
       <h1><img src=\"view/image/payment/nimbbllogo.png\" alt=\"nimbbl\" /></h1>
   </div>
 <div class=\"content\">
  <div class=\"panel-body\">
        <div class=\"col-sm-10\"> 
\t\t\t<div class=\"form-group required\">
\t\t\t\t<label class=\"col-sm-20 control-label\" for=\"payment_nimbbl_webhook\">Webhook URL: ";
        // line 37
        echo ($context["webhook_url"] ?? null);
        echo "</label>
\t\t\t</div>
\t\t\t<div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_title\"><span data-toggle=\"tooltip\" title=\"";
        // line 40
        echo ($context["help_title"] ?? null);
        echo "\">";
        echo ($context["entry_title"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_title\" value=\"";
        // line 42
        echo ($context["payment_nimbbl_title"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_title"] ?? null);
        echo "\" id=\"payment_nimbbl_title\" class=\"form-control\" />
              ";
        // line 43
        if (($context["error_title"] ?? null)) {
            echo " 
              <div class=\"text-danger\">";
            // line 44
            echo ($context["error_title"] ?? null);
            echo "</div>
              ";
        }
        // line 46
        echo "            </div>
\t\t\t</div>
\t\t\t
\t\t\t<div class=\"form-group required\">
\t\t\t\t<label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_mode\">";
        // line 50
        echo ($context["entry_mode"] ?? null);
        echo "</label>
\t\t\t\t<div class=\"col-sm-10\">
\t\t\t\t\t<select name=\"payment_nimbbl_mode\" id=\"payment_nimbbl_mode\" class=\"form-control\">
\t\t\t\t\t\t";
        // line 53
        if ((($context["payment_nimbbl_mode"] ?? null) == "live")) {
            // line 54
            echo "\t\t\t\t\t\t<option value=\"live\" selected=\"selected\">";
            echo ($context["text_live"] ?? null);
            echo "</option>
\t\t\t\t\t\t";
        } else {
            // line 56
            echo "\t\t\t\t\t\t<option value=\"live\">";
            echo ($context["text_live"] ?? null);
            echo "</option>
\t\t\t\t\t\t";
        }
        // line 58
        echo "\t\t\t\t\t\t";
        if ((($context["payment_nimbbl_mode"] ?? null) == "test")) {
            // line 59
            echo "\t\t\t\t\t\t<option value=\"test\" selected=\"selected\">";
            echo ($context["text_test"] ?? null);
            echo "</option>
\t\t\t\t\t\t";
        } else {
            // line 61
            echo "\t\t\t\t\t\t<option value=\"test\">";
            echo ($context["text_test"] ?? null);
            echo "</option>
\t\t\t\t\t\t";
        }
        // line 62
        echo "                
\t\t\t\t\t</select>
\t\t\t\t</div>
          </div>
\t\t  <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_testendpoint\"><span data-toggle=\"tooltip\" title=\"";
        // line 67
        echo ($context["help_testendpoint"] ?? null);
        echo "\">";
        echo ($context["entry_testendpoint"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_testendpoint\" value=\"";
        // line 69
        echo ($context["payment_nimbbl_testendpoint"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_testendpoint"] ?? null);
        echo "\" id=\"payment_nimbbl_testendpoint\" class=\"form-control\" />
              ";
        // line 70
        if (($context["error_testendpoint"] ?? null)) {
            echo " 
              <div class=\"text-danger\">";
            // line 71
            echo ($context["error_testendpoint"] ?? null);
            echo "</div>
              ";
        }
        // line 73
        echo "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_testpublickey\"><span data-toggle=\"tooltip\" title=\"";
        // line 76
        echo ($context["help_testpublickey"] ?? null);
        echo "\">";
        echo ($context["entry_testpublickey"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_testpublickey\" value=\"";
        // line 78
        echo ($context["payment_nimbbl_testpublickey"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_testpublickey"] ?? null);
        echo "\" id=\"payment_nimbbl_testpublickey\" class=\"form-control\" />
              ";
        // line 79
        if (($context["error_testpublickey"] ?? null)) {
            echo " 
              <div class=\"text-danger\">";
            // line 80
            echo ($context["error_testpublickey"] ?? null);
            echo "</div>
              ";
        }
        // line 82
        echo "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_testprivatekey\"><span data-toggle=\"tooltip\" title=\"";
        // line 85
        echo ($context["help_testprivatekey"] ?? null);
        echo "\">";
        echo ($context["entry_testprivatekey"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_testprivatekey\" value=\"";
        // line 87
        echo ($context["payment_nimbbl_testprivatekey"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_testprivatekey"] ?? null);
        echo "\" id=\"payment_nimbbl_testprivatekey\" class=\"form-control\" />
              ";
        // line 88
        if ( !twig_test_empty(($context["error_testprivatekey"] ?? null))) {
            // line 89
            echo "              <div class=\"text-danger\">";
            echo ($context["error_testprivatekey"] ?? null);
            echo "</div>
              ";
        }
        // line 91
        echo "            </div>
          </div>
\t\t  <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_liveendpoint\"><span data-toggle=\"tooltip\" title=\"";
        // line 94
        echo ($context["help_liveendpoint"] ?? null);
        echo "\">";
        echo ($context["entry_liveendpoint"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_liveendpoint\" value=\"";
        // line 96
        echo ($context["payment_nimbbl_liveendpoint"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_liveendpoint"] ?? null);
        echo "\" id=\"payment_nimbbl_liveendpoint\" class=\"form-control\" />
              ";
        // line 97
        if (($context["error_liveendpoint"] ?? null)) {
            echo " 
              <div class=\"text-danger\">";
            // line 98
            echo ($context["error_liveendpoint"] ?? null);
            echo "</div>
              ";
        }
        // line 100
        echo "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_livepublickey\"><span data-toggle=\"tooltip\" title=\"";
        // line 103
        echo ($context["help_livepublickey"] ?? null);
        echo "\">";
        echo ($context["entry_livepublickey"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_livepublickey\" value=\"";
        // line 105
        echo ($context["payment_nimbbl_livepublickey"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_livepublickey"] ?? null);
        echo "\" id=\"payment_nimbbl_livepublickey\" class=\"form-control\" />
              ";
        // line 106
        if (($context["error_livepublickey"] ?? null)) {
            echo " 
              <div class=\"text-danger\">";
            // line 107
            echo ($context["error_livepublickey"] ?? null);
            echo "</div>
              ";
        }
        // line 109
        echo "            </div>
          </div>
          <div class=\"form-group required\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_liveprivatekey\"><span data-toggle=\"tooltip\" title=\"";
        // line 112
        echo ($context["help_liveprivatekey"] ?? null);
        echo "\">";
        echo ($context["entry_liveprivatekey"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_liveprivatekey\" value=\"";
        // line 114
        echo ($context["payment_nimbbl_liveprivatekey"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_liveprivatekey"] ?? null);
        echo "\" id=\"payment_nimbbl_liveprivatekey\" class=\"form-control\" />
              ";
        // line 115
        if ( !twig_test_empty(($context["error_liveprivatekey"] ?? null))) {
            // line 116
            echo "              <div class=\"text-danger\">";
            echo ($context["error_liveprivatekey"] ?? null);
            echo "</div>
              ";
        }
        // line 118
        echo "            </div>
          </div>
\t\t  <div class=\"form-group\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_geo_zone_id\"><span data-toggle=\"tooltip\" title=\"";
        // line 121
        echo ($context["help_geozone"] ?? null);
        echo "\">";
        echo ($context["entry_geo_zone"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <select name=\"payment_nimbbl_geo_zone_id\" id=\"payment_nimbbl_geo_zone_id\" class=\"form-control\">
                <option value=\"0\">";
        // line 124
        echo ($context["text_all_zones"] ?? null);
        echo "</option>
                ";
        // line 125
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["geo_zones"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["geo_zone"]) {
            // line 126
            echo "                ";
            if ((twig_get_attribute($this->env, $this->source, $context["geo_zone"], "geo_zone_id", [], "any", false, false, false, 126) == ($context["payment_nimbbl_geo_zone_id"] ?? null))) {
                // line 127
                echo "                <option value=\"";
                echo (($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 = $context["geo_zone"]) && is_array($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4) || $__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4 instanceof ArrayAccess ? ($__internal_f607aeef2c31a95a7bf963452dff024ffaeb6aafbe4603f9ca3bec57be8633f4["geo_zone_id"] ?? null) : null);
                echo "\" selected=\"selected\">";
                echo (($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 = $context["geo_zone"]) && is_array($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144) || $__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144 instanceof ArrayAccess ? ($__internal_62824350bc4502ee19dbc2e99fc6bdd3bd90e7d8dd6e72f42c35efd048542144["name"] ?? null) : null);
                echo "</option>
                ";
            } else {
                // line 129
                echo "                <option value=\"";
                echo (($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b = $context["geo_zone"]) && is_array($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b) || $__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b instanceof ArrayAccess ? ($__internal_1cfccaec8dd2e8578ccb026fbe7f2e7e29ac2ed5deb976639c5fc99a6ea8583b["geo_zone_id"] ?? null) : null);
                echo "\">";
                echo (($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 = $context["geo_zone"]) && is_array($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002) || $__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002 instanceof ArrayAccess ? ($__internal_68aa442c1d43d3410ea8f958ba9090f3eaa9a76f8de8fc9be4d6c7389ba28002["name"] ?? null) : null);
                echo "</option>
                ";
            }
            // line 131
            echo "                ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['geo_zone'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 132
        echo "              </select>
            </div>
          </div>          
          <div class=\"form-group\">
            <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_total\"><span data-toggle=\"tooltip\" title=\"";
        // line 136
        echo ($context["help_total"] ?? null);
        echo "\">";
        echo ($context["entry_total"] ?? null);
        echo "</span></label>
            <div class=\"col-sm-10\">
              <input type=\"text\" name=\"payment_nimbbl_total\" value=\"";
        // line 138
        echo ($context["payment_nimbbl_total"] ?? null);
        echo "\" placeholder=\"";
        echo ($context["entry_total"] ?? null);
        echo "\" id=\"payment_nimbbl_total\" class=\"form-control\" />
            </div>
          </div>
          
          <!--order_status-->
          
          <div class=\"form-group\">
             <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_order_status_id\"><span data-toggle=\"tooltip\" title=\"";
        // line 145
        echo ($context["help_orderstatus"] ?? null);
        echo "\">";
        echo ($context["entry_order_status"] ?? null);
        echo "</span></label>
               <div class=\"col-sm-10\">
                 <select name=\"payment_nimbbl_order_status_id\" id=\"payment_nimbbl_order_status_id\" class=\"form-control\">
                    ";
        // line 148
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["order_statuses"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["order_status"]) {
            // line 149
            echo "                    ";
            if ((twig_get_attribute($this->env, $this->source, $context["order_status"], "order_status_id", [], "any", false, false, false, 149) == ($context["payment_nimbbl_order_status_id"] ?? null))) {
                // line 150
                echo "                      <option value=\"";
                echo (($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 = $context["order_status"]) && is_array($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4) || $__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4 instanceof ArrayAccess ? ($__internal_d7fc55f1a54b629533d60b43063289db62e68921ee7a5f8de562bd9d4a2b7ad4["order_status_id"] ?? null) : null);
                echo "\" selected=\"selected\">";
                echo (($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 = $context["order_status"]) && is_array($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666) || $__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666 instanceof ArrayAccess ? ($__internal_01476f8db28655ee4ee02ea2d17dd5a92599be76304f08cd8bc0e05aced30666["name"] ?? null) : null);
                echo "</option>
\t\t\t\t\t";
            } else {
                // line 152
                echo "                      <option value=\"";
                echo (($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e = $context["order_status"]) && is_array($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e) || $__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e instanceof ArrayAccess ? ($__internal_01c35b74bd85735098add188b3f8372ba465b232ab8298cb582c60f493d3c22e["order_status_id"] ?? null) : null);
                echo "\">";
                echo (($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52 = $context["order_status"]) && is_array($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52) || $__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52 instanceof ArrayAccess ? ($__internal_63ad1f9a2bf4db4af64b010785e9665558fdcac0e8db8b5b413ed986c62dbb52["name"] ?? null) : null);
                echo "</option>
                    ";
            }
            // line 154
            echo "                    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['order_status'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 155
        echo "                 </select>
               </div> 
          </div>
          
          <!--order_status-->          
          
          <!--order_fail_status-->
          
          <div class=\"form-group\">
             <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_order_fail_status_id\"><span data-toggle=\"tooltip\" title=\"";
        // line 164
        echo ($context["help_orderfailstatus"] ?? null);
        echo "\">";
        echo ($context["entry_order_fail_status"] ?? null);
        echo "</span></label>
               <div class=\"col-sm-10\">
                 <select name=\"payment_nimbbl_order_fail_status_id\" id=\"payment_nimbbl_order_fail_status_id\" class=\"form-control\">
                    ";
        // line 167
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(($context["order_statuses"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["order_status"]) {
            // line 168
            echo "                    ";
            if ((twig_get_attribute($this->env, $this->source, $context["order_status"], "order_status_id", [], "any", false, false, false, 168) == ($context["payment_nimbbl_order_fail_status_id"] ?? null))) {
                // line 169
                echo "                      <option value=\"";
                echo (($__internal_f10a4cc339617934220127f034125576ed229e948660ebac906a15846d52f136 = $context["order_status"]) && is_array($__internal_f10a4cc339617934220127f034125576ed229e948660ebac906a15846d52f136) || $__internal_f10a4cc339617934220127f034125576ed229e948660ebac906a15846d52f136 instanceof ArrayAccess ? ($__internal_f10a4cc339617934220127f034125576ed229e948660ebac906a15846d52f136["order_status_id"] ?? null) : null);
                echo "\" selected=\"selected\">";
                echo (($__internal_887a873a4dc3cf8bd4f99c487b4c7727999c350cc3a772414714e49a195e4386 = $context["order_status"]) && is_array($__internal_887a873a4dc3cf8bd4f99c487b4c7727999c350cc3a772414714e49a195e4386) || $__internal_887a873a4dc3cf8bd4f99c487b4c7727999c350cc3a772414714e49a195e4386 instanceof ArrayAccess ? ($__internal_887a873a4dc3cf8bd4f99c487b4c7727999c350cc3a772414714e49a195e4386["name"] ?? null) : null);
                echo "</option>
                    ";
            } else {
                // line 171
                echo "                      <option value=\"";
                echo (($__internal_d527c24a729d38501d770b40a0d25e1ce8a7f0bff897cc4f8f449ba71fcff3d9 = $context["order_status"]) && is_array($__internal_d527c24a729d38501d770b40a0d25e1ce8a7f0bff897cc4f8f449ba71fcff3d9) || $__internal_d527c24a729d38501d770b40a0d25e1ce8a7f0bff897cc4f8f449ba71fcff3d9 instanceof ArrayAccess ? ($__internal_d527c24a729d38501d770b40a0d25e1ce8a7f0bff897cc4f8f449ba71fcff3d9["order_status_id"] ?? null) : null);
                echo "\">";
                echo (($__internal_f6dde3a1020453fdf35e718e94f93ce8eb8803b28cc77a665308e14bbe8572ae = $context["order_status"]) && is_array($__internal_f6dde3a1020453fdf35e718e94f93ce8eb8803b28cc77a665308e14bbe8572ae) || $__internal_f6dde3a1020453fdf35e718e94f93ce8eb8803b28cc77a665308e14bbe8572ae instanceof ArrayAccess ? ($__internal_f6dde3a1020453fdf35e718e94f93ce8eb8803b28cc77a665308e14bbe8572ae["name"] ?? null) : null);
                echo "</option>
                    ";
            }
            // line 173
            echo "                    ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['order_status'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 174
        echo "                 </select>
               </div> 
          </div>
          
          <!--order_fail_status-->    
          
          <div class=\"form-group\">
              <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_status\"><span data-toggle=\"tooltip\" title=\"";
        // line 181
        echo ($context["help_pluginstatus"] ?? null);
        echo "\">";
        echo ($context["entry_status"] ?? null);
        echo "</span></label>
                 <div class=\"col-sm-10\">
                   <select name=\"payment_nimbbl_status\" id=\"payment_nimbbl_status\" class=\"form-control\">
                      ";
        // line 184
        if ((($context["payment_nimbbl_status"] ?? null) == true)) {
            // line 185
            echo "                         <option value=\"1\" selected=\"selected\">";
            echo ($context["text_enabled"] ?? null);
            echo "</option>
                         <option value=\"0\">";
            // line 186
            echo ($context["text_disabled"] ?? null);
            echo "</option>
                      ";
        } else {
            // line 188
            echo "                         <option value=\"1\">";
            echo ($context["text_enabled"] ?? null);
            echo "</option>
                         <option value=\"0\" selected=\"selected\">";
            // line 189
            echo ($context["text_disabled"] ?? null);
            echo "</option>
                      ";
        }
        // line 191
        echo "                   </select>
                   ";
        // line 192
        if (($context["error_status"] ?? null)) {
            // line 193
            echo "                \t<div class=\"text-danger\">";
            echo ($context["error_status"] ?? null);
            echo "</div>
            \t \t";
        }
        // line 195
        echo "                 </div>                  
          </div>
          
          <div class=\"form-group\">
               <label class=\"col-sm-2 control-label\" for=\"payment_nimbbl_sort_order\"><span data-toggle=\"tooltip\" title=\"";
        // line 199
        echo ($context["help_sortorder"] ?? null);
        echo "\">";
        echo ($context["entry_sort_order"] ?? null);
        echo "</span></label>
               \t <div class=\"col-sm-10\">
                      <input type=\"text\" name=\"payment_nimbbl_sort_order\" value=\"";
        // line 201
        echo ($context["payment_nimbbl_sort_order"] ?? null);
        echo "\"  id=\"payment_nimbbl_sort_order\" class=\"form-control\"size=\"1\" />
                 </div>
          </div> 

      </div>
     </div>
     </div>
    <!--// Nimbbl End //-->
<hr />
</form>
";
        // line 211
        echo ($context["footer"] ?? null);
    }

    public function getTemplateName()
    {
        return "extension/payment/nimbbl.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  578 => 211,  565 => 201,  558 => 199,  552 => 195,  546 => 193,  544 => 192,  541 => 191,  536 => 189,  531 => 188,  526 => 186,  521 => 185,  519 => 184,  511 => 181,  502 => 174,  496 => 173,  488 => 171,  480 => 169,  477 => 168,  473 => 167,  465 => 164,  454 => 155,  448 => 154,  440 => 152,  432 => 150,  429 => 149,  425 => 148,  417 => 145,  405 => 138,  398 => 136,  392 => 132,  386 => 131,  378 => 129,  370 => 127,  367 => 126,  363 => 125,  359 => 124,  351 => 121,  346 => 118,  340 => 116,  338 => 115,  332 => 114,  325 => 112,  320 => 109,  315 => 107,  311 => 106,  305 => 105,  298 => 103,  293 => 100,  288 => 98,  284 => 97,  278 => 96,  271 => 94,  266 => 91,  260 => 89,  258 => 88,  252 => 87,  245 => 85,  240 => 82,  235 => 80,  231 => 79,  225 => 78,  218 => 76,  213 => 73,  208 => 71,  204 => 70,  198 => 69,  191 => 67,  184 => 62,  178 => 61,  172 => 59,  169 => 58,  163 => 56,  157 => 54,  155 => 53,  149 => 50,  143 => 46,  138 => 44,  134 => 43,  128 => 42,  121 => 40,  115 => 37,  102 => 27,  97 => 25,  93 => 23,  85 => 19,  83 => 18,  77 => 14,  66 => 12,  62 => 11,  56 => 8,  50 => 7,  46 => 6,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "extension/payment/nimbbl.twig", "");
    }
}
