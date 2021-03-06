<?php
use \Mockery as m;

class OmiseTest extends Mockery\Adapter\Phpunit\MockeryTestCase
{
    private $checkout_form;
    private $omise;
    private $omise_transaction_model;
    private $setting;
    private $smarty;

    public function setup()
    {
        $unit_test_helper = new UnitTestHelper();

        $unit_test_helper->getMockedPaymentModule();

        $this->checkout_form = $this->getMockBuilder(get_class(new CheckoutForm()))
            ->setMethods(
                array(
                    'getListOfExpirationYear',
                )
            )
            ->getMock();

        m::mock('alias:\Configuration')
            ->shouldReceive('get')
            ->shouldReceive('deleteByName');

        $this->omise_transaction_model = $this->getMockedOmiseTransactionModel();

        $this->setting = $unit_test_helper->getMockedSetting();

        $this->smarty = $this->getMockBuilder(get_class(new stdClass()))
            ->setMockClassName('Smarty')
            ->setMethods(
                array(
                    'assign',
                )
            )
            ->getMock();

        $this->omise = new Omise();
        $this->omise->_path = '_path/';
        $this->omise->context = $this->getMockedContext();
        $this->omise->setCheckoutForm($this->checkout_form);
        $this->omise->setOmiseTransactionModel($this->omise_transaction_model);
        $this->omise->setSetting($this->setting);
        $this->omise->setSmarty($this->smarty);
    }

    public function testConstructor_whenInitiateTheNewInstance_theDefaultValueOfTheAttributeSettingMustBeAvailable()
    {
        $omise = new Omise();

        $setting = $omise->getSetting();

        $this->assertInstanceOf(get_class(new Setting()), $setting);
    }

    public function testName_theNameThatUsedToReferenceInTheProgramMustBe_omise()
    {
        $this->assertEquals('omise', $this->omise->name);
    }

    public function testDisplayName_theNameThatUsedToDisplayToTheMerchantMustBe_Omise()
    {
        $this->assertEquals('Omise', $this->omise->displayName);
    }

    public function testNeedInstance_configureTheModule_noNeedToLoadModuleAtTheBackendModulePage()
    {
        $this->assertEquals(0, $this->omise->need_instance);
    }

    public function testBootstrap_configureTheModule_bootstrapTemplateIsRequired()
    {
        $this->assertEquals(true, $this->omise->bootstrap);
    }

    public function testGetContent_merchantOpenTheSettingPage_retrieveSettingDataFromTheDatabaseAndDisplayOnThePage()
    {
        $this->omise->context->link->method('getModuleLink')->willReturn('webhooks_endpoint');
        $this->setting->method('isInternetBankingEnabled')->willReturn('internet_banking_status');
        $this->setting->method('isModuleEnabled')->willReturn('module_status');

        $this->smarty->expects($this->once())
            ->method('assign')
            ->with(array(
                'internet_banking_status' => 'internet_banking_status',
                'live_public_key' => 'live_public_key',
                'live_secret_key' => 'live_secret_key',
                'module_status' => 'module_status',
                'sandbox_status' => 'sandbox_status',
                'submit_action' => 'submit_action',
                'test_public_key' => 'test_public_key',
                'test_secret_key' => 'test_secret_key',
                'title' => 'title',
                'three_domain_secure_status' => 'three_domain_secure_status',
                'webhooks_endpoint' => 'webhooks_endpoint',
            ));

        $this->omise->getContent();
    }

    public function testGetContent_merchantSaveSetting_saveTheSettingData()
    {
        $this->setting->method('isInternetBankingEnabled')->willReturn('internet_banking_status');
        $this->setting->method('isSubmit')->willReturn(true);

        $this->setting->expects($this->once())->method('save');

        $this->omise->getContent();
    }

    public function testHookHeader_internetBankingIsEnabled_addOmiseInternetBankingCssToThePage() {
        $this->setting->method('isInternetBankingEnabled')->willReturn(true);

        $this->omise->context->controller->expects($this->once())
            ->method('addCSS')
            ->with('_path/css/omise_internet_banking.css', 'all');

        $this->omise->hookHeader();
    }

    public function testHookHeader_internetBankingIsEnabled_addJqueryFancyboxToThePage() {
        $this->setting->method('isInternetBankingEnabled')->willReturn(true);

        $this->omise->context->controller->expects($this->once())
            ->method('addJqueryPlugin')
            ->with('fancybox');

        $this->omise->hookHeader();
    }

    public function testHookPaymentOptions_internetBankingIsEnabled_displayInternetBankingPaymentOption() {
        $this->setting->method('isInternetBankingEnabled')->willReturn(true);
        m::mock('alias:\OmisePluginHelperCharge')
            ->shouldReceive('isCurrentCurrencyApplicable')
            ->andReturn(true);
        $this->omise->method('display')->willReturn('internetBankingPayment');

        m::mock('overload:PrestaShop\PrestaShop\Core\Payment\PaymentOption')
            ->shouldReceive('setCallToActionText')->with('Internet Banking')->once()
            ->shouldReceive('setForm')->with('internetBankingPayment')->once()
            ->shouldReceive('setModuleName')->with(Omise::INTERNET_BANKING_PAYMENT_OPTION_NAME);

        $payment_options = $this->omise->hookPaymentOptions();

        $this->assertNotNull($payment_options);
    }

    public function testHookPaymentOptions_moduleStatusIsDisabledAndInternetBankingIsDisabled_paymentOptionsIsNull()
    {
        $this->setting->method('isModuleEnabled')->willReturn(false);

        $payment_options = $this->omise->hookPaymentOptions();

        $this->assertNull($payment_options);
    }

    public function testHookPaymentOptions_moduleStatusIsEnabled_displayCardPaymentOption()
    {
        $this->setting->method('isModuleEnabled')->willReturn(true);
        m::mock('alias:\OmisePluginHelperCharge')
            ->shouldReceive('isCurrentCurrencyApplicable')
            ->andReturn(true);
        $this->omise->method('display')->willReturn('payment');

        m::mock('overload:PrestaShop\PrestaShop\Core\Payment\PaymentOption')
            ->shouldReceive('setCallToActionText')->with($this->setting->getTitle())->once()
            ->shouldReceive('setForm')->with('payment')->once()
            ->shouldReceive('setModuleName')->with(Omise::CARD_PAYMENT_OPTION_NAME);

        $payment_options = $this->omise->hookPaymentOptions();

        $this->assertNotNull($payment_options);
    }

    public function testInstall_installationIsSuccess_true()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, true, true));
        $this->omise_transaction_model->method('createTable')->willReturn(true);
        $this->setting->method('saveTitle')->with(Omise::DEFAULT_CARD_PAYMENT_TITLE)->willReturn(true);

        $this->assertTrue($this->omise->install());
    }

    public function testInstall_createTableIsFail_false()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, true, true));
        $this->omise_transaction_model->method('createTable')->willReturn(false);

        $this->assertFalse($this->omise->install());
    }

    public function testInstall_parentInstallationIsFail_false()
    {
        $this->omise->method('install')->willReturn(false);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, true, true));
        $this->omise_transaction_model->method('createTable')->willReturn(true);

        $this->assertFalse($this->omise->install());
    }

    public function testInstall_registerHookForDisplayOrderConfirmationIsFail_false()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(false, true, true));
        $this->omise_transaction_model->method('createTable')->willReturn(true);

        $this->assertFalse($this->omise->install());
    }

    public function testInstall_registerHookForHeaderIsFail_false()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, false, true));
        $this->omise_transaction_model->method('createTable')->willReturn(true);

        $this->assertFalse($this->omise->install());
    }

    public function testInstall_registerHookForPaymentOptionsIsFail_false()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, true, false));
        $this->omise_transaction_model->method('createTable')->willReturn(true);

        $this->assertFalse($this->omise->install());
    }

    public function testInstall_saveDefaultCardPaymentTitleIsFail_false()
    {
        $this->omise->method('install')->willReturn(true);
        $this->omise->method('registerHook')->will($this->onConsecutiveCalls(true, true, true));
        $this->omise_transaction_model->method('createTable')->willReturn(true);
        $this->setting->method('saveTitle')->with(Omise::DEFAULT_CARD_PAYMENT_TITLE)->willReturn(false);

        $this->assertFalse($this->omise->install());
    }

    public function testUninstall_uninstallTheModule_theSettingMustBeDeleted()
    {
        $this->setting->expects($this->once())
            ->method('delete');

        $this->omise->uninstall();
    }

    public function testUninstall_uninstallIsSuccess_true()
    {
        $this->omise->method('uninstall')->willReturn(true);
        $this->omise->method('unregisterHook')->will($this->onConsecutiveCalls(true, true, true));

        $this->assertTrue($this->omise->uninstall());
    }

    public function testUninstall_parentUninstallIsFail_false()
    {
        $this->omise->method('uninstall')->willReturn(false);
        $this->omise->method('unregisterHook')->will($this->onConsecutiveCalls(true, true, true));

        $this->assertFalse($this->omise->uninstall());
    }

    public function testUninstall_unregisterHookForDisplayOrderConfirmationIsFail_false()
    {
        $this->omise->method('uninstall')->willReturn(true);
        $this->omise->method('unregisterHook')->will($this->onConsecutiveCalls(false, true, true));

        $this->assertFalse($this->omise->uninstall());
    }

    public function testUninstall_unregisterHookForHeaderIsFail_false()
    {
        $this->omise->method('uninstall')->willReturn(true);
        $this->omise->method('unregisterHook')->will($this->onConsecutiveCalls(true, false, true));

        $this->assertFalse($this->omise->uninstall());
    }

    public function testUninstall_unregisterHookPaymentOptionsIsFail_false()
    {
        $this->omise->method('uninstall')->willReturn(true);
        $this->omise->method('unregisterHook')->will($this->onConsecutiveCalls(true, true, false));

        $this->assertFalse($this->omise->uninstall());
    }

    private function getMockedContext()
    {
        $controller = $this->getMockBuilder(get_class(new stdClass()))
            ->setMethods(
                array(
                    'addCSS',
                    'addJqueryPlugin',
                )
            )
            ->getMock();

        $currency = $this->getMockBuilder(get_class(new stdClass()));
        $currency->iso_code = 'THB';

        $link = $this->getMockBuilder(get_class(new stdClass()))
            ->setMethods(
                array(
                    'getModuleLink',
                )
            )
            ->getMock();

        $context = $this->getMockBuilder(get_class(new stdClass()))->getMock();
        $context->controller = $controller;
        $context->currency = $currency;
        $context->link = $link;

        return $context;
    }

    private function getMockedOmiseTransactionModel()
    {
        $omise_transaction_model = $this->getMockBuilder(get_class(new stdClass()))
            ->setMockClassName('OmiseTransactionModel')
            ->setMethods(
                array(
                    'add',
                    'createTable',
                )
            )
            ->getMock();

        return $omise_transaction_model;
    }
}
