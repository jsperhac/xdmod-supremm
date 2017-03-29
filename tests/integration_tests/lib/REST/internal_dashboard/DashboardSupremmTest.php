<?php

namespace IntegrationTests\REST\internal_dashboard;

class DashboardSupremmTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $xdmodConfig = array( "decodetextasjson" => True );
        $this->xdmodhelper = new \TestHarness\XdmodTestHelper($xdmodConfig);

        $this->endpoint = 'rest/v0.1/supremm_dataflow/';

        // validate as program officer, e.g.
        $this->validateAsUser = 'po';
    }

    private function invalidSupremmResourceEntries($params)
    {
        // without performing validation: expect to receive a 400

        $result = $this->xdmodhelper->get($this->endpoint . 'resources', $params);
        $this->assertEquals(403, $result[1]['http_code']);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], false);
    }

    private function validateSupremmResourceEntries($params)
    {
        $this->xdmodhelper->authenticate( $this->validateAsUser );

        $result = $this->xdmodhelper->get($this->endpoint . 'resources', $params);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], true);

        $data = $result[0]['data'];
        foreach ($data as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('name', $item);
        }

        return $data;
    }

   private function invalidSupremmDbstatsEntries($params)
    {
        // without performing validation: expect to receive a 400:

        $result = $this->xdmodhelper->get($this->endpoint . 'dbstats', $params);
        $this->assertEquals(400, $result[1]['http_code']);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], false);
    }

    private function validateSupremmDbstatsEntries($db)
    {
        $this->xdmodhelper->authenticate( $this->validateAsUser );

        $params = array(
            'resource_id' => $this->testFetchResourceId(),
            'db_id' => $db
        );
        $result = $this->xdmodhelper->get($this->endpoint . 'dbstats', $params);
        $this->assertEquals(200, $result[1]['http_code']);

        $this->assertArrayHasKey('success', $result[0]);
        $this->assertEquals($result[0]['success'], true);

        $item = $result[0]['data']['data'];
        $this->assertGreaterThanOrEqual(1, $item);

        return $item;
    }


    public function testInvalidSupremmResourceEntries()
    {
        $this->invalidSupremmResourceEntries(NULL);
    }

    public function testInvalidSupremmDbstatsEntries()
    {
        $this->invalidSupremmDbstatsEntries(NULL);
    }

    public function testResourceNullParam()
    {
        $data = $this->validateSupremmResourceEntries(NULL);

        // should return at least one item
        $this->assertGreaterThanOrEqual(1, $data);
    }

    // return arbitrary ResourceId
    public function testFetchResourceId()
    {
        $result = $this->validateSupremmResourceEntries(NULL);

        $resourceid = $result[0]['id'];
        return $resourceid;
    }

    // fetch summarydb stats
    public function testFetchDbstatsSummary($db = 'summarydb') {

        $item = $this->validateSupremmDbstatsEntries($db);

        $this->assertArrayHasKey('total', $item);
        $this->assertArrayHasKey('avgObjSize', $item);
        $this->assertArrayHasKey('storageSize', $item);
        $this->assertArrayHasKey('size', $item);
        $this->assertArrayHasKey('processed', $item);
        $this->assertArrayHasKey('pending', $item);
    }

    // fetch accountdb stats
    public function testFetchDbstatsAccount($db = 'accountdb') {

        $item = $this->validateSupremmDbstatsEntries($db);

        $this->assertArrayHasKey('total', $item);
        $this->assertArrayHasKey('approx_size', $item);
        $this->assertArrayHasKey('last_job', $item);
        $this->assertArrayHasKey('last_job_tm', $item);
        $this->assertArrayHasKey('processed', $item);
        $this->assertArrayHasKey('pending', $item);
    }

    // fetch jobfact stats
    public function testFetchDbstatsJobfact($db = 'jobfact') {

        $item = $this->validateSupremmDbstatsEntries($db);

        $this->assertArrayHasKey('total', $item);
        $this->assertArrayHasKey('approx_size', $item);
        $this->assertArrayHasKey('last_job', $item);
        $this->assertArrayHasKey('last_job_tm', $item);
    }

    // fetch aggregates stats
    public function testFetchDbstatsAggregates($db = 'aggregates') {

        $item = $this->validateSupremmDbstatsEntries($db);

        $this->assertArrayHasKey('approx_size', $item);
        $this->assertArrayHasKey('last_day', $item);
        $this->assertArrayHasKey('last_day_tm', $item);
    }
}
