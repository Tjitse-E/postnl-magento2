<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Test\Unit\Service\Timeframe\Filters\Days;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use TIG\PostNL\Config\Provider\Webshop;
use TIG\PostNL\Service\Timeframe\Filters\Days\CutOffTimes;
use TIG\PostNL\Service\Timeframe\IsPastCutOff;
use TIG\PostNL\Test\TestCase;

class CutOffTimesTest extends TestCase
{
    public $instanceClass = CutOffTimes::class;

    /**
     * @dataProvider \TIG\PostNL\Test\Fixtures\Timeframes\Days\DataProvider::noFiltering
     *
     * @param $input
     * @param $output
     */
    public function testDoesNotFilterWhenBeforeCutOff($input, $output)
    {
        $this->assertEquals($output, $this->loadInstance(false)->filter($input));
    }

    /**
     * @dataProvider \TIG\PostNL\Test\Fixtures\Timeframes\Days\DataProvider::cutOffTimePassed
     *
     * @param $input
     * @param $output
     */
    public function testDoesFilterAfterCutOff($input, $output)
    {
        $this->assertEquals($output, $this->loadInstance(true)->filter($input));
    }

    /**
     * @dataProvider \TIG\PostNL\Test\Fixtures\Timeframes\Days\DataProvider::cutOffNextDayRemoved
     *
     * @param $input
     * @param $output
     */
    public function testDoesFilterOnlyTheNextDay($input, $output)
    {
        $result = $this->loadInstance(true)->filter($input);
        $this->assertEquals($output, $result);
    }

    /**
     * @param bool $isPastCutOff
     *
     * @return CutOffTimes
     */
    private function loadInstance($isPastCutOff)
    {
        $isPastCutOffMock = $this->getFakeMock(IsPastCutOff::class, true);
        $this->mockFunction($isPastCutOffMock, 'calculate', $isPastCutOff);

        $todayDateTime = new \DateTime('18-11-2016');
        $todayMock = $this->getMock(TimezoneInterface::class);
        $this->mockFunction($todayMock, 'date', $todayDateTime);

        $dateLoaderMock = $this->getMock(TimezoneInterface::class);
        $dateMethod = $dateLoaderMock->method('date');
        $dateMethod->willReturnCallback(function ($date) {
            return new \DateTime($date);
        });

        return $this->getInstance([
            'isPastCutOff' => $isPastCutOffMock,
            'dateLoader' => $dateLoaderMock,
            'today' => $todayMock,
        ]);
    }
}
