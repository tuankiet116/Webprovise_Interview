<?php
class Travel
{

    private $travels;
    public function __construct()
    {
        $this->list();
    }

    public function list()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        $this->travels = json_decode($response);
    }

    public function getCostByCompany()
    {
        $costs = array();
        foreach ($this->travels as $travel) {
            if (!isset($costs[$travel->companyId])) {
                $costs[$travel->companyId] = (float)$travel->price;
            } else {
                $costs[$travel->companyId] += (float)$travel->price;
            }
        }
        return $costs;
    }
}

class Company
{
    private $companies = [];
    public function __construct()
    {
        $this->list();
    }

    public function list()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // $this->companies = array_map(function($company) {
        //     return $company->id => $company;
        // },json_decode(curl_exec($ch)));
        $this->companies = json_decode(curl_exec($ch));
        // var_dump($this->companies);
        curl_close($ch);
    }

    public function getRoot($costs)
    {
        $parents = [];
        foreach ($this->companies as $company) {
            if ($company->parentId == false) {
                array_push($parents, [
                    "id" => $company->id,
                    "name" => $company->name,
                    "cost" => $costs[$company->id],
                    "children" => []
                ]);
            }
        }
        return $parents;
    }

    public function detectChildren(&$parent, $costs, $result = [])
    {
        if (isset($parent)) {
            foreach ($this->companies as $company) {
                if ($parent["id"] == $company->parentId) {
                    $child = [
                        "id" => $company->id,
                        "name" => $company->name,
                        "cost" => isset($costs[$company->id]) ? (float)$costs[$company->id] : 0,
                        "children" => []
                    ];
                    $childOfChild = $this->detectChildren($child, $costs);
                    $parent["cost"] += (float)$child["cost"];
                    array_push($parent["children"], $childOfChild);
                }
            }
        }
        return $parent;
    }

    public function companiesByCost($costs)
    {
        $tree = [];
        $roots = $this->getRoot($costs);
        foreach ($roots as $root) {
            array_push($tree, $this->detectChildren($root, $costs));
        }
        return $tree;
    }
}

class TestScript
{
    public function execute()
    {
        $start = microtime(true);

        $companyModel = new Company();
        $travelModel = new Travel();

        $costs = $travelModel->getCostByCompany();
        $result = $companyModel->companiesByCost($costs);
        echo json_encode($result);
        echo 'Total time: ' .  (microtime(true) - $start);
    }
}
(new TestScript())->execute();
