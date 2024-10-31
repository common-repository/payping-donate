if(typeof jQuery == 'function')
    {
      jQuery("#payPingDonate_UseCustomStyle").change(function(){
        if(jQuery("#payPingDonate_UseCustomStyle").prop('checked') == true)
          jQuery("#payPingDonate_CustomStyleBox").show(500);
        else
          jQuery("#payPingDonate_CustomStyleBox").hide(500);
      });
    }