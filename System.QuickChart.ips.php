<?php

declare(strict_types=1);

################################################################################
# Script:   System.QuickChart.ips.php
# Version:  1.1.20230223
# Author:   Heiko Wilknitz (@Pitti)
#
# A PHP client for the quickchart.io chart image API.
# Based on the quickchart-php implementation
# @link https://github.com/typpo/quickchart-php
#
# ------------------------------ MIT licence -----------------------------------
#
# Copyright (c) 2020 QuickChart.io
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
#
# ------------------------------ Changelog -------------------------------------
#
# 05.02.2023 - Initalversion (v1.0)
# 23.02.2023 - Fix ratio parameter (v1.1)
#
################################################################################

/**
 * A PHP client for the quickchart.io chart image API.
 *
 * @author        Heiko Wilknitz (@Pitti) <heiko@wilkware.de>
 * @license       https://choosealicense.com/licenses/mit/
 * @version       2023.02.23
 *
 */
class QuickChart
{
    public string $protocol;
    public string $host;
    public int $port;

    public string $config;
    public int $width;
    public int $height;
    public float $ratio;
    public string $format;
    public string $background;
    public string $api;
    public int $version;

    /**
     * Constructor - pass configuration options as array
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->protocol = isset($options['protocol']) ? $options['protocol'] : 'https';
        $this->host = isset($options['host']) ? $options['host'] : 'quickchart.io';
        $this->port = isset($options['port']) ? $options['port'] : 443;
        $this->width = isset($options['width']) ? $options['width'] : 500;
        $this->height = isset($options['height']) ? $options['height'] : 300;
        $this->ratio = isset($options['ratio']) ? $options['ratio'] : 1.0;
        $this->format = isset($options['format']) ? $options['format'] : 'png';
        $this->background = isset($options['background']) ? $options['background'] : 'transparent';
        $this->api = isset($options['api']) ? $options['api'] : '';
        $this->version = isset($options['version']) ? $options['version'] : 0;
    }

    /**
     * Sets configuration options for the chart generation.
     *
     * @param string $config Json structure with configuration options.
     */
    public function setConfig(string $config)
    {
        $this->config = $config;
    }

    /**
     * Sets the width of the canvas.
     *
     * @param int $width Width in pixel.
     */
    public function setWidth(int $width)
    {
        $this->width = $width;
    }

    /**
     * Sets the height of the canvas.
     *
     * @param int $height Height in pixel.
     */
    public function setHeight(int $height)
    {
        $this->height = $height;
    }

    /**
     * Sets device pixel ratio.
     *
     * @param float $ratio Device pixel ratio, default 1.0
     */
    public function setRatio(float $ratio)
    {
        $this->ratio = $ratio;
    }

    /**
     * Sets the format of output.
     *
     * @param string $format Accepted values: png, webp, svg, pdf, default: png
     */
    public function setFormat(strung $format)
    {
        $this->format = $format;
    }

    /**
     * Background of the chart canvas. Accepts rgb format (rgb(255,255,120)),
     * colors (red), and URL-encoded hex values (%23ff00ff).
     *
     * @param string $background Background color value.
     */
    public function setBackground(string $background)
    {
        $this->background = $background;
    }

    /**
     * Users api key for authorisation.
     *
     * @param string $api  API key (optional).
     */
    public function setApi(string $api)
    {
        $this->api = $api;
    }

    /**
     * Sets the Chart.js version number (2,3 or 4).
     *
     * @param int $version Version number, default null (=2)
     */
    public function setVersion(int $version)
    {
        $this->version = $version;
    }

    /**
     * Returns the configuration options as json string.
     *
     * @return string Json encoded string.
     */
    public function getConfig(): string
    {
        return $this->config;
    }

    /**
     * Returns the full qualifed chartjs url.
     *
     * @return string URL
     */
    public function getUrl(): string
    {
        $config = urlencode($this->getConfig());
        $width = $this->width;
        $height = $this->height;
        $ratio = number_format($this->ratio, 1);
        $format = $this->format;
        $background = $this->background;

        $url = sprintf($this->getRootEndpoint() . '/chart?c=%s&w=%d&h=%d&devicePixelRatio=%f&format=%s&bkg=%s', $configStr, $width, $height, $ratio, $format, $background);

        if ($this->api) {
            $url .= '&key=' . $this->api;
        }

        if ($this->version) {
            $url .= '&v=' . $this->version;
        }

        return $url;
    }

    /**
     * Returns the short version of chartjs url.
     *
     * @return string URL
     */
    public function getShortUrl(): string
    {
        if ($this->host != 'quickchart.io') {
            throw new Exception('Short URLs must use quickchart.io host');
        }
        $ch = curl_init($this->getRootEndpoint() . '/chart/create');
        $postData = [
            'backgroundColor'  => $this->background,
            'width'            => $this->width,
            'height'           => $this->height,
            'devicePixelRatio' => number_format($this->ratio, 1),
            'format'           => $this->format,
            'chart'            => $this->getConfig(),
        ];
        if ($this->api) {
            $postData['key'] = $this->api;
        }
        if ($this->version) {
            $postData['version'] = $this->version;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        curl_close($ch);
        // Note: do not dereference json_decode directly for 5.3 compatibility.
        $ret = json_decode($result, true);
        return $ret['url'];
    }

    /**
     * Chartjs as binary output.
     *
     * @return mixed Binary content or false
     */
    public function toBinary(): string
    {
        $ch = curl_init($this->getRootEndpoint() . '/chart');
        $postData = [
            'backgroundColor'  => $this->background,
            'width'            => $this->width,
            'height'           => $this->height,
            'devicePixelRatio' => number_format($this->ratio, 1),
            'format'           => $this->format,
            'chart'            => $this->getConfig(),
        ];
        if (!empty($this->api)) {
            $postData['key'] = $this->api;
        }
        if ($this->version != 0) {
            $postData['version'] = $this->version;
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            throw new ErrorException(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * Writes output to file
     *
     * @param string $path File path
     */
    public function toFile(string $path)
    {
        $data = $this->toBinary();
        file_put_contents($path, $data);
    }

    /**
     * Gets the service root endpoint url.
     *
     * @return string Url root endpoint
     */
    protected function getRootEndpoint(): string
    {
        return $this->protocol . '://' . $this->host . ':' . $this->port;
    }
}
