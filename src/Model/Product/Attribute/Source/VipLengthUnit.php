<?php
namespace Meanbee\VipMembership\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Product status functionality model
 */
class VipLengthUnit extends AbstractSource implements SourceInterface, OptionSourceInterface
{
    const UNIT_DAYS = 'days';
    const UNIT_WEEKS = 'weeks';
    const UNIT_MONTHS = 'months';
    const UNIT_YEARS = 'years';

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [
            self::UNIT_DAYS => __('Days'),
            self::UNIT_WEEKS => __('Weeks'),
            self::UNIT_MONTHS => __('Months'),
            self::UNIT_YEARS => __('Years'),
        ];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
