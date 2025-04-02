<?php
namespace App\Services\Form;
class ProductFormBuilder
{
    protected $fields = [];
    protected $validationRules = [];

    public function __construct()
    {
        $this->initializeFields();
        $this->initializeValidationRules();
    }

    protected function initializeFields()
    {
        $this->fields = [
            'name' => [
                'type' => 'text',
                'label' => 'Product Name',
                'required' => true,
            ],
            'description' => [
                'type' => 'textarea',
                'label' => 'Product Description',
                'required' => false,
            ],
            'price' => [
                'type' => 'number',
                'label' => 'Product Price',
                'required' => true,
            ],
            'category_id' => [
                'type' => 'select',
                'label' => 'Category',
                'options' => [], // This should be populated with categories
                'required' => true,
            ],
            'is_active' => [
                'type' => 'checkbox',
                'label' => 'Is Active',
                'required' => false,
            ],
        ];
    }

    protected function initializeValidationRules()
    {
        $this->validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'is_active' => 'boolean',
        ];
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getValidationRules()
    {
        return $this->validationRules;
    }
}
