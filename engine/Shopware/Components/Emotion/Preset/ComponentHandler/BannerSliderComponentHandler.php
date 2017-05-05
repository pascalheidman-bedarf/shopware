<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Components\Emotion\Preset\ComponentHandler;

class BannerSliderComponentHandler extends AbstractComponentHandler
{
    const COMPONENT_TYPE = 'emotion-components-banner-slider';

    const ELEMENT_DATA_KEY = 'banner_slider';

    /**
     * {@inheritdoc}
     */
    public function supports($componentType)
    {
        return $componentType === self::COMPONENT_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function import(array $element)
    {
        if (!isset($element['data'], $element['assets'])) {
            return $element;
        }

        return $this->processElementData($element);
    }

    /**
     * {@inheritdoc}
     */
    public function export(array $element)
    {
        if (!isset($element['data'])) {
            return $element;
        }

        $element = $this->prepareElementExport($element);

        return $element;
    }

    /**
     * @param array $element
     *
     * @return array
     */
    private function processElementData(array $element)
    {
        $data = $element['data'];
        $assets = $element['assets'];

        /** @var array $elementData */
        foreach ($data as &$elementData) {
            if ($elementData['key'] === self::ELEMENT_DATA_KEY) {
                $sliders = json_decode($elementData['value'], true);
                if (!is_array($sliders)) {
                    break;
                }

                foreach ($sliders as $key => &$slide) {
                    $media = $this->doAssetImport($assets[$slide['path']]);

                    $slide['path'] = $media->getPath();
                    $slide['mediaId'] = $media->getId();
                }
                unset($slide);
                $elementData['value'] = json_encode($sliders);

                break;
            }
        }
        unset($elementData);

        $element['data'] = $data;
        unset($element['assets']);

        return $element;
    }

    /**
     * @param array $element
     *
     * @return array
     */
    private function prepareElementExport(array $element)
    {
        $element['assets'] = [];
        $data = $element['data'];

        /** @var array $elementData */
        foreach ($data as &$elementData) {
            if ($elementData['key'] === self::ELEMENT_DATA_KEY) {
                $sliders = json_decode($elementData['value'], true);
                if (!is_array($sliders)) {
                    break;
                }

                foreach ($sliders as $key => &$slide) {
                    $assetHash = uniqid('asset-', true);
                    $element['assets'][$assetHash] = $this->mediaService->getUrl($slide['path']);
                    $slide['path'] = $assetHash;
                }
                unset($slide);
                $elementData['value'] = json_encode($sliders);

                break;
            }
        }
        unset($elementData);

        $element['data'] = $data;

        return $element;
    }
}