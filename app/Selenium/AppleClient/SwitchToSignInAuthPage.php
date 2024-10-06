<?php

namespace App\Selenium\AppleClient;

use App\Selenium\AppleClient\Page\SignIn\SignInSelectPhonePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthenticationPage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithDevicePage;
use App\Selenium\AppleClient\Page\SignIn\TwoFactorAuthWithPhonePage;
use App\Selenium\Exception\PageException;
use App\Selenium\Page\Page;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;

trait SwitchToSignInAuthPage
{

    /**
     * @return Page|TwoFactorAuthWithDevicePage|TwoFactorAuthWithPhonePage
     * @throws PageException
     */
    protected function switchToSignInAuthPage(): TwoFactorAuthWithPhonePage|Page|TwoFactorAuthWithDevicePage
    {
        try {

            $page =  $this->attemptSwitchToPage(new TwoFactorAuthenticationPage($this->connector));

            /**
             * @var $page TwoFactorAuthenticationPage
             */
            if ($page->isVerifyDevice()) {
                return new TwoFactorAuthWithDevicePage($this->connector);
            }

            return new TwoFactorAuthWithPhonePage($this->connector);

        } catch (NoSuchElementException|TimeoutException $e) {
            // Ignore and attempt next page
        }

        try {

            return $this->attemptSwitchToPage(new SignInSelectPhonePage($this->connector));

        } catch (NoSuchElementException|TimeoutException $e) {
            // Ignore since we'll rethrow at the end
        }

        throw new PageException($this, 'Can not switch to sign in auth page');
    }

    /**
     * @param Page $page
     * @return Page
     * @throws NoSuchElementException
     */
    private function attemptSwitchToPage(Page $page): Page
    {
        if ($page->isCurrentTitle()) {
            return $page;
        }

        throw new NoSuchElementException("The page with title '{$page->title()}' is not currently displayed.");
    }
}
