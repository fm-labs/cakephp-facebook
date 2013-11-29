<?php
App::uses('FacebookGraphSource','Facebook.Model/Datasource');
App::uses('FacebookAppModel','Facebook.Model');

class FacebookGraphSourceTest extends CakeTestCase {

    /**
     * @var FacebookGraphSource
     */
    public $ds;

    public function setUp() {
        parent::setUp();

        $this->ds = new TestFacebookGraphSource();
    }

    public function testConstruct() {
        $this->skipIf(true, 'Test me');
    }

    public function testParseFilterParams() {

        // level 1
        $params = array(
            'fields' => array('name','first_name','last_name'),
            'limit' => 1,
            'offset' => 2,
            'height' => 50
        );
        $expected = 'fields=name,first_name,last_name&limit=1&offset=2&height=50';
        $result = $this->ds->parseFilterParams($params, 1);
        $this->assertEqual($result, $expected);

        // level 2
        $params = array(
            'fields' => array('name','first_name','last_name'),
            'limit' => 1,
            'offset' => 2,
            'height' => 50
        );
        $expected = 'fields(name,first_name,last_name).limit(1).offset(2).height(50)';
        $result = $this->ds->parseFilterParams($params, 2);
        $this->assertEqual($result, $expected);
    }

    public function testBuildPath() {

        // first-level
        $path = '/me/friends';
        $params = array(
            'fields' => array('name','first_name','last_name'),
            'limit' => 1,
            'offset' => 2
        );
        $expected = '/me/friends?fields=name,first_name,last_name&limit=1&offset=2';
        $result = $this->ds->buildPath($path, $params);
        $this->assertEqual($result, $expected);

        // more levels
        $path = '/me/friends';
        $params = array(
            'fields' => array(
                'name',
                'first_name',
                'picture' => array(
                    'height' => 50,
                ),
                'family' => array(
                    'limit' => 2,
                    'fields' => array(
                        'name',
                        'relationship',
                    )
                )
            ),
            'limit' => 2,
            'offset' => 2
        );
        $expected = '/me/friends?fields=name,first_name,picture.height(50),family.limit(2).fields(name,relationship)&limit=2&offset=2';
        $result = $this->ds->buildPath($path, $params);
        $this->assertEqual($result, $expected);
    }

    public function testRead() {
        $this->skipIf(true, 'Test me');
    }

    public function testUpdate() {
        $this->skipIf(true, 'Test me');
    }

    public function testDelete() {
        $this->skipIf(true, 'Test me');
    }
}

class TestFacebookGraphSource extends FacebookGraphSource {

    public function parseFilterParams($params, $level = 1) {
        return $this->_parseFilterParams($params, $level);
    }
}

class FacebookGraphSourceTestModel extends FacebookAppModel {

}