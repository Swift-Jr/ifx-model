/*
    testing
    full suite: grunt test
    specific file: grunt test --unitpath=models/test.php
    groups: grunt test --group=failing

    annotate functions with @group name e.g. @group failing
 */

module.exports = function (grunt) {
    'use strict';

    //INIT
    require('jit-grunt')(grunt);
    grunt.initConfig({});

    grunt.loadNpmTasks('grunt-force-task');

    grunt.config('pkg', grunt.file.readJSON('package.json'));

    grunt.config('config', {
        source: 'source/',
        build: 'build/',
        ciroot: 'ci',
        phpunitroot: 'phpunit'
    });

    //TASK LIST

    var installList = [
        'clean:node_ci',
        'shell:codeignighter',
        'clean:phpunit',
        'shell:phpunit',
        'shell:phpunit_install'
    ];

    var retestList = [
        'copy:sourcephp',
        'copy:phpunitci',
        'copy:citests',
        'shell:tests'
    ];

    var testList = [
        'force:clean:default',
        'copy:codeignighter',
        'copy:sourcephp',
        'copy:ciconfig',
        'copy:phpunitci',
        'copy:citests',
        'shell:tests'
    ];

    var testDebugList = [
        'copy:sourcephp',
        'copy:phpunitci',
        'copy:citests',
        'shell:tests_debug'
    ];

    grunt.registerTask('install', installList);
    grunt.registerTask('test', testList);
    grunt.registerTask('retest', retestList);
    grunt.registerTask('debug', testDebugList);

    //TASK CONFIG

    grunt.config('clean', {
        default: ['<%=config.build %>'],
        node_ci: ['<%=config.ciroot %>'],
        phpunit: ['<%=config.phpunitroot %>']
    });

    grunt.config('copy', {

        codeignighter: {
            cwd: '<%=config.ciroot %>',
            src: [
                '**',
                //ignore
                '!user_guide_src/**',
                '!build-release.sh',
                '!composer.json',
                '!contributing.md',
                '!DCO.txt',
                '!license.txt',
                '!phpdoc.dist.xml',
                '!readme.rst',
                '!tests/**',
            ],
            dot: true,
            mode: '0777',
            dest: '<%=config.build %>',
            expand: true
        },

        phpunitci: {
            cwd: 'phpunit/application/tests/',
            src: [
                '**',
                '!controllers/Welcome_test.php'
            ],
            dest: '<%=config.build %>application/tests',
            expand: true,
            mode: '0777'
        },

        citests: {
            cwd: 'tests/ci/',
            src: [
                '**',
            ],
            dest: '<%=config.build %>application/tests',
            expand: true,
            mode: '0777'
        },

        sourcephp: {
            expand: true,
            cwd: '<%=config.source %>ci/',
            src: ['**'],
            mode: '0777',
            dest: '<%=config.build %>'
        },

        ciconfig: {
            expand: true,
            cwd: '<%=config.source %>ci/application/ifx/setup/',
            src: ['**'],
            mode: '0777',
            dest: '<%=config.build %>application'
        }
    });

    var testPath = '';
    if(grunt.option('unitpath')){
        testPath = grunt.option('unitpath');
    }

    if(grunt.option('group')){
        testPath = '--group ' + grunt.option('group');
    }

    grunt.config('shell', {
        codeignighter : {
            command: 'git clone --branch master https://github.com/bcit-ci/CodeIgniter.git <%=config.ciroot %>',
        },
        phpunit_install: {
            command: 'curl https://phar.phpunit.de/phpunit-6.0.13.phar -o phpunit.phar; chmod +x phpunit.phar; sudo mv phpunit.phar /usr/local/bin/phpunit; phpunit --version;'
        },
        phpunit: {
            command: 'git clone --branch master https://github.com/kenjis/ci-phpunit-test.git <%=config.phpunitroot %>',
        },
        tests: {
            command: 'cd build/application/tests/; phpunit '+testPath+';',
        },
        tests_debug: {
            command: 'cd build/application/tests/; export XDEBUG_CONFIG="remote_host=localhost remote_enable=1 remote_autostart=1"; phpunit '+testPath+';',
        }
    });

    //END TASK CONFIG
};
