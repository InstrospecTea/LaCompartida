<?php
require_once dirname(__FILE__).'/../conf.php';

class PasswordStrength {

  public static function Rate($password) {
    if (strlen($password)==0 || !$password) {
      echo 1;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/accounts/RatePassword?Passwd=' . urlencode($password));
    curl_exec($ch);
    curl_closE($ch);
  }

  public static function PrintCSS($width = "210px") {
    echo "<style type=\"text/css\" media=\"screen\">
      div#passwordMeterFormItem {
        width: $width;
      }
      table#passwordMeter {
        width: 100%;
        height: 10px;
        margin: 0;
        clear: both;
      }
      span#passwordStrengthLabel {
        float: left;
      }
      table#passwordMeter tbody, table#passwordMeter tr {
        border: none;
      }
      table#passwordMeter td {
        padding: 0;
        height: 10px;
      }
      table#passwordMeter td#barLeft {
        background-color: #e0e0e0;
        width: 0%;
      }
      table#passwordMeter td#barRight {
        background-color: #e0e0e0;
        width: 100%;
      }
      table#passwordMeter td#barLeft.Weak {
        width: 25%;
        background-color: #AA0033;
      }
      table#passwordMeter td#barRight.Weak {
        width: 75%;
      }
      table#passwordMeter td#barLeft.Fair {
        width: 50%;
        background-color: #FFCC33;
      }
      table#passwordMeter td#barRight.Fair {
        width: 50%;
      }
      table#passwordMeter td#barLeft.Good {
        width: 75%;
        background-color: #6699CC;
      }
      table#passwordMeter td#barRight.Good {
        width: 25%;
      }
      table#passwordMeter td#barLeft.Strong {
        width: 100%;
        background-color: #008000;
      }
      table#passwordMeter td#barRight.Strong {
        width: 0%;
      }
      span#passwordStrengthDescription {
        display: block;
        float: right;
      }
      span#passwordStrengthDescription.Weak {
        color: #AA0033;
      }
      span#passwordStrengthDescription.Fair {
        color: #FFCC33;
      }
      span#passwordStrengthDescription.Good {
        color: #6699CC;
      }
      span#passwordStrengthDescription.Strong {
        color: #008000;
      }
    </style>";
  }

  public static function PrintHTML() {
    echo "
    <div id='passwordMeterFormItem' style='float:left'>
      <label>
        <span id='passwordStrengthLabel'>Fortaleza</span>
        <span id='passwordStrengthDescription'></span>
      </label>
      <table id='passwordMeter'>
        <tr>
          <td id='barLeft'></td>
          <td id='barRight'></td>
        </tr>
      </table>
    </div>
    ";
  }

  public static function PrintJS($password_field = "password") {
    echo "
    var WITHOUT_CLASIFICATION = 5;
    var password_callback = function(passwordCode) {
      var word = 'Sin clasificar';
      var strclass = 'without';
      switch (passwordCode) {
      case '0':
        word = 'Muy insegura'
        strclass = 'Weak'
        break;
      case '1':
        word = 'Insegura';
        strclass = 'Weak'
        break;
      case '2':
        word = 'Normal';
        strclass = 'Fair';
        break;
      case '3':
        word = 'Buena';
        strclass = 'Good';
        break;
      case '4':
        word = 'Segura';
        strclass = 'Strong';
        break;
      }
      jQuery('table#passwordMeter td#barLeft').removeClass();
      jQuery('table#passwordMeter td#barRight').removeClass();
      jQuery('table#passwordMeter td#barLeft').addClass(strclass);
      jQuery('table#passwordMeter td#barRight').addClass(word);
      jQuery('span#passwordStrengthDescription').html(word);
      jQuery('span#passwordStrengthDescription').removeClass();
      jQuery('span#passwordStrengthDescription').addClass(strclass);
    };

    jQuery('#$password_field').typeWatch({
      callback: function(val) {
        var password = val;
        if (password) {
          jQuery.ajax({
            type: 'POST',
            url: '../interfaces/ajax.php',
            data: { accion: 'rate_password', password: password},
            success: password_callback,
            async:true
          });
        } else {
          password_callback(WITHOUT_CLASIFICATION);
        }
      },
      wait: 250,
      highlight: false,
      captureLength: 0
    });
    ";
  }

  public static function PrintJSReset(){
    echo "password_callback(WITHOUT_CLASIFICATION);";
  }

}