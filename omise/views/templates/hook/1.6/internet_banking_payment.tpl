<div class="row">
  <div class="col-xs-12">
    <p class="payment_module">
      <div class="box">
        <div class="row">
          <div class="col-sm-12">
            <h3>{l s='Internet Banking' mod='omise'}</h3>
          </div>
          <div class="col-sm-12">
            <form id="omiseInternetBankingCheckoutForm" method="post" action="{$link->getModuleLink('omise', 'internetbankingpayment', [], true)|escape:'html'}">
              <ul class="omise-internet-banking">
                <li class="item">
                  <input class="no-uniform" id="omiseInternetBankingScb" name="offsite" type="radio" value="internet_banking_scb" autocomplete="off">
                  <label for="omiseInternetBankingScb">
                    <div class="omise-logo-wrapper scb">
                      <img src="{$urls.base_url}/modules/omise/img/scb.svg" class="scb">
                    </div>
                    <span class="title">{l s='Siam Commercial Bank' mod='omise'}</span><br>
                  </label>
                </li>
                <li class="item">
                  <input class="no-uniform" id="omiseInternetBankingKtb" name="offsite" type="radio" value="internet_banking_ktb" autocomplete="off">
                  <label for="omiseInternetBankingKtb">
                    <div class="omise-logo-wrapper ktb">
                      <img src="{$urls.base_url}/modules/omise/img/ktb.svg" class="ktb">
                    </div>
                    <span class="title">{l s='Krungthai Bank' mod='omise'}</span><br>
                  </label>
                </li>
                <li class="item">
                  <input class="no-uniform" id="omiseInternetBankingBay" name="offsite" type="radio" value="internet_banking_bay" autocomplete="off">
                  <label for="omiseInternetBankingBay">
                    <div class="omise-logo-wrapper bay">
                      <img src="{$urls.base_url}/modules/omise/img/bay.svg" class="bay">
                    </div>
                    <span class="title">{l s='Krungsri Bank' mod='omise'}</span><br>
                  </label>
                </li>
                <li class="item">
                  <input class="no-uniform" id="omiseInternetBankingBbl" name="offsite" type="radio" value="internet_banking_bbl" autocomplete="off">
                  <label for="omiseInternetBankingBbl">
                    <div class="omise-logo-wrapper bbl">
                      <img src="{$urls.base_url}/modules/omise/img/bbl.svg" class="bbl">
                    </div>
                    <span class="title">{l s='Bangkok Bank' mod='omise'}</span><br>
                  </label>
                </li>
              </ul>
              <div class="fee-warning"><label>{l s='Your bank may charge a small fee for internet banking payments.' mod='omise'}</label></div>
              <button class="button btn btn-default standard-checkout button-medium" id="omiseInternetBankingCheckoutButton">
                <span>{l s='Submit Payment' mod='omise'}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </p>
  </div>
</div>


<script>

  // IMPORTANT - this window.xxx stuff looks weird and unnecessary, but it's necessary to make
  // the JS work correctly when the checkout is in one-page mode. It would appear that
  // dynamically created script blocks do not run in the global context
  
  window.omise_msg_select_bank = "{l s='Please select a bank before continuing.' js=1 mod='omise'}";
  window.omiseDisplayMessage = function omiseDisplayMessage(message) {
    if ($.prototype.fancybox) {
      $.fancybox.open([
          {
            type: 'inline',
            autoScale: true,
            minHeight: 30,
            content: '<p class="fancybox-error">' + message + '</p>',
          }],
        {
          padding: 0,
        });
    } else {
      alert(message);
    }
  }

  window.omiseHasAnyBankSelected = function omiseHasAnyBankSelected() {
    var selectedBank = document.getElementsByName('offsite');

    for (var i = 0; i < selectedBank.length; i++) {
      if (selectedBank[i].checked == true) {
        return true;
      }
    }

    return false;
  }

  window.omiseInternetBankingCheckout = function omiseInternetBankingCheckout(event) {
    event.preventDefault();

    if (omiseHasAnyBankSelected() == false) {
      omiseDisplayMessage(omise_msg_select_bank);
      return false;
    }

    document.getElementById('omiseInternetBankingCheckoutForm').submit();
  }

  /**
   * Remove the Uniform style.
   *
   * To display the list of banks at the Omise internet banking payment method, currently, it uses the similar
   * style sheet with others Omise plugins to remain the display consistency.
   *
   * But PrestaShop uses a jQuery plugin, Uniform, to style the elements. This plugin adds additional elements and it
   * make the different display.
   *
   * So, to remain the display consistency with the similar style sheet, the Uniform style for some elements
   * need to be removed.
   *
   * Uniform is a jQuery plugin that has been bundled in PrestaShop to style the elements.
   * @see /themes/default-bootstrap/js/autoload/15-jquery.uniform-modified.js
   *
   * Reference about Uniform, jQuery plugin, on GitHub: https://github.com/square/uniform
   */
  window.omiseRestoreUniformStyle = function omiseRestoreUniformStyle() {
    $.uniform.restore('.no-uniform');
  }

  document.getElementById('omiseInternetBankingCheckoutButton').addEventListener('click', function(event) {
    window.omiseInternetBankingCheckout(event);
  });

  window.addEventListener('load', function() {
    window.omiseRestoreUniformStyle();
  });

  window.addEventListener('resize', function() {
    window.omiseRestoreUniformStyle();
  });

  window.setTimeout(window.omiseRestoreUniformStyle, 100);
</script>
