<?php
namespace Ekomi\Request;

/**
 * Class PutProductOther
 * @package Ekomi\Request
 */
class PutProductOther{
    private $imageUrl;
    private $brandName;
    private $productIds = array();
    private $links = array();
    private $categories = array();
    private $research;
    private $metaMatrix;

    /**
     * Get API Query
     * @return array
     */
    public function getQuery(){
        $query = array(
            'image_url' => $this->getImageUrl(),
            'brand_name' => $this->getBrandName(),
            'product_ids' => $this->getProductIds(),
            'links' => $this->getLinks(),
            'categories' => $this->getCategories(),
            'research' => $this->getResearch(),
            'meta_matrix' => json_encode($this->getMetaMatrix())
        );

        return $query;
    }

    /**
     * Add Links - canonical, image, html
     * Image can be jpg, .jpeg, .gif, .png
     *
     * @param $url
     * @param string $type
     * @return PutProductOther
     */
    public function addLinks($url,$type='canonical'){
        if($type==='image'){
            $this->links[] = array('rel' => 'related', 'type' => $this->getImageMime($url), 'href' => $url);
        }
        else if($type==='canonical') {
            $this->links[] = array('rel' => 'canonical', 'type' => 'text/html', 'href' => $url);
       }
        else {
            $this->links[] = array('rel' => 'related', 'type' => 'text/html', 'href' => $url);
        }
        return $this;
    }

    /**
     * @param $mpn
     * @return PutProductOther
     */
    public function setMpn($mpn){
        $this->productIds['mpn'] = $mpn;
        return $this;
    }

    /**
     * @param $upc
     * @return PutProductOther
     */
    public function setUpc($upc){
        $this->productIds['upc'] = $upc;
        return $this;
    }

    /**
     * @param $ean
     * @return PutProductOther
     */
    public function setEan($ean){
        $this->productIds['ean'] = $ean;
        return $this;
    }

    /**
     * @param $isbn
     * @return PutProductOther
     */
    public function setIsbn($isbn){
        $this->productIds['isbn'] = $isbn;
        return $this;
    }

    /**
     * @param $gbase
     * @return PutProductOther
     */
    public function setGbase($gbase){
        $this->productIds['gbase'] = $gbase;
        return $this;
    }

    /**
     * Add Category
     *
     * @param $name
     * @param string $id
     * @return PutProductOther
     */
    public function addCategory($name,$id=''){
        $data = array('name'=>$name);
        if($id!==''){
            $data['id'] = $id;
        }
        $this->categories[] = $data;
        return $this;
    }

    /**
     * Add Product Research
     * @param $researchId
     * @return PutProductOther
     */
    public function addResearch($researchId){
        $this->research['add'][] = array('research_id'=>$researchId);
        return $this;
    }

    /**
     * Add Meta Matrix
     * @param $key
     * @param $value
     * @return PutProductOther
     */
    public function addMetaMatrix($key,$value){
        $this->metaMatrix[$key] = $value;
        return $this;
    }

    /**
     * Get Image Mime
     * @param $image
     * @return string
     */
    public static function getImageMime($image){
        $mime = '';
        if(preg_match('/\.jpg|\.jpeg$/i', $image)){
            $mime = 'image/jpeg';
        }
        else if(preg_match('/\.gif$/i', $image)){
            $mime = 'image/gif';
        }
        else if(preg_match('/\.png$/i', $image)){
            $mime = 'image/png';
        }
        return $mime;
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @param mixed $imageUrl
     * @return PutProductOther
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brandName;
    }

    /**
     * @param mixed $brandName
     * @return PutProductOther
     */
    public function setBrandName($brandName)
    {
        $this->brandName = $brandName;
        return $this;
    }

    /**
     * @return array
     */
    public function getProductIds()
    {
        return $this->productIds;
    }

    /**
     * @param array $productIds
     * @return PutProductOther
     */
    public function setProductIds($productIds)
    {
        $this->productIds = $productIds;
        return $this;
    }

    /**
     * @return array
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @param array $links
     * @return PutProductOther
     */
    public function setLinks($links)
    {
        $this->links = $links;
        return $this;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param array $categories
     * @return PutProductOther
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResearch()
    {
        return $this->research;
    }

    /**
     * @param mixed $research
     * @return PutProductOther
     */
    public function setResearch($research)
    {
        $this->research = $research;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMetaMatrix()
    {
        return $this->metaMatrix;
    }

    /**
     * @param mixed $metaMatrix
     * @return PutProductOther
     */
    public function setMetaMatrix($metaMatrix)
    {
        $this->metaMatrix = $metaMatrix;
        return $this;
    }

}