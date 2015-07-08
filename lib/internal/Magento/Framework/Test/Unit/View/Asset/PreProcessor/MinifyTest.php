<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\Test\Unit\View\Asset\PreProcessor;

use Magento\Framework\App\State;
use Magento\Framework\View\Asset\PreProcessor\Minify;

/**
 * Unit test for Magento\Framework\View\Asset\PreProcessor\Minify
 */
class MinifyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\View\Asset\PreProcessor\Minify
     */
    protected $minify;

    /**
     * @var \Magento\Framework\View\Asset\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\Code\Minifier\AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $adapterMock;

    /**
     * @var \Magento\Framework\App\State|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appStateMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder('Magento\Framework\View\Asset\ConfigInterface')
            ->setMethods(['isAssetMinification'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->adapterMock = $this->getMockBuilder('Magento\Framework\Code\Minifier\AdapterInterface')
            ->setMethods(['minify'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->appStateMock = $this->getMockBuilder('Magento\Framework\App\State')
            ->disableOriginalConstructor()
            ->getMock();

        $this->minify = new Minify(
            $this->configMock,
            $this->adapterMock,
            $this->appStateMock
        );
    }

    /**
     * @param string $targetPath
     * @param string $originalPath
     * @param int $minifyCalls
     * @param int $setContentCalls
     * @param bool $isEnabled
     * @param int $appMode
     * @return void
     * @dataProvider processDataProvider
     */
    public function testProcess($targetPath, $originalPath, $minifyCalls, $setContentCalls, $isEnabled, $appMode)
    {
        $chainMock = $this->getMockBuilder('Magento\Framework\View\Asset\PreProcessor\Chain')
            ->disableOriginalConstructor()
            ->getMock();
        $chainMock
            ->expects($this->any())
            ->method('getTargetAssetPath')
            ->willReturn($targetPath);
        $chainMock
            ->expects($this->exactly($setContentCalls))
            ->method('setContent')
            ->with('minified content');
        $chainMock
            ->expects($this->any())
            ->method('getContent')
            ->willReturn('original content');
        $chainMock
            ->expects($this->any())
            ->method('getOrigAssetPath')
            ->willReturn($originalPath);

        $this->adapterMock
            ->expects($this->exactly($minifyCalls))
            ->method('minify')
            ->with('original content')
            ->willReturn('minified content');

        $this->configMock
            ->expects($this->any())
            ->method('isAssetMinification')
            ->willReturnMap([['css', $isEnabled]]);

        $this->appStateMock
            ->expects($this->any())
            ->method('getMode')
            ->willReturn($appMode);

        $this->minify->process($chainMock);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            ['test.min.css', 'test.css', 1, 1, true, State::MODE_PRODUCTION],
            ['test.min.css', 'test.css', 1, 1, true, State::MODE_DEFAULT],
            ['test.min.css', 'test.min.css', 0, 0, true, State::MODE_PRODUCTION],
            ['test.min.css', 'test.css', 0, 0, true, State::MODE_DEVELOPER],
            ['test.jpeg', 'test.jpeg', 0, 0, true, State::MODE_DEFAULT],
            ['test.css', 'test.css', 0, 0, true, State::MODE_DEFAULT],
            ['test.jpeg', 'test.jpeg', 0, 0, true, State::MODE_DEFAULT],
            ['test.css', 'test.css', 0, 0, true, State::MODE_DEFAULT],
            ['test.min.css', 'test.css', 0, 0, false, State::MODE_DEFAULT],
            ['test.css', 'test.css', 0, 0, false, State::MODE_DEFAULT]
        ];
    }
}
