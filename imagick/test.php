<?php
test();

function test()
{
    $task = array(
        'action'     => 'crop',
        'sourceFile' => 'imagick_test1.jpg',
        'destinationFile' => 'imagick_test1_crop.jpg',
        'params' => array(
            'width'   => 1300,
            'height'  => 1400,
            'x'       => 1500,
            'y'       => 1600,
        ),
        'subTasks' => array(
            array(
                'action' => 'scale',
                'destinationFile' => 'imagick_test1_crop_scale.jpg',
                'params' => array(
                    'scale'   => 600,
                ),
                'subTasks' => array(
                    array(
                        'action' => 'extent',
                        'destinationFile' => 'imagick_test1_crop_scale_extent.jpg',
                        'params' => array(
                            'width'   => 1000,
                            'height'  => 700,
                            'x'       => 'center',
                            'y'       => 'center',
                            'background' => 'blue',
                        ),
                        'subTasks' => array(
                            array(
                                'action' => 'rotate',
                                'destinationFile' => 'imagick_test1_crop_scale_extent_rotate.jpg',
                                'params' => array(
                                    'degrees' => 90,
                                ),
                                'subTasks' => array(
                                    array(
                                        'action' => 'filter',
                                        'destinationFile' => 'imagick_test1_crop_scale_extent_rotate_filter.jpg',
                                        'params' => array(
                                            'radius' => 2,
                                        ),
                                    ),
                                    array(
                                        'action' => 'blur',
                                        'destinationFile' => 'imagick_test1_crop_scale_extent_rotate_blur.jpg',
                                        'params' => array(
                                            'radius' => 2,
                                            'sigma'  => 20,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    array(
                        'action' => 'watermark',
                        'params' => array(
                            'type'    => 'position', // mosaic, position
                            'x'       => 'right',
                            'y'       => 'bottom',
                            'watermark' => 'ria-watermark.png',
                        ),
                        'subTasks' => array(
                            array(
                                'action'          => 'save',
                                'destinationFile' => 'imagick_test1_crop_scale_watermark.jpg',
                            ),
                        ),
                    ),
                    array(
                        'action' => 'watermark',
                        'destinationFile' => 'imagick_test1_crop_scale_watermark2.jpg',
                        'params' => array(
                            'type'    => 'position', // mosaic, position
                            'x'       => -20,
                            'y'       => -20,
                            'watermark' => 'ria-watermark.png',
                        ),
                    ),
                    array(
                        'action' => 'annotate',
                        'destinationFile' => 'imagick_test1_crop_scale_annotate.jpg',
                        'params' => array(
                            'x'        => 'right',
                            'y'        => -50,
                            'text'     => 'Медведев и Ко',
                            'fontSize' => 20,
                            'fontColor' => 'blue',
                            'degrees'  => 45,
                        ),
                    ),
                    array(
                        'action' => 'annotate',
                        'destinationFile' => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                        'params' => array(
                            'type'     => 'mosaic',
                            'text'     => '   Медведев и Ко   ',
                            'fontSize' => 20,
                            'fontColor' => '#00FF00',
                            'degrees'  => -45,
                            'composite' => 'Imagick::COMPOSITE_MODULATE',
                        ),
                        'subTasks' => array(
                            array(
                                'action' => 'mail',
                                'params' => array(
                                    'to' => 'yurineudachin@mail.ru',
                                    'subject'  => 'Imagick test result for Yuri',
                                    'filename' => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                                ),
                            ),
                            array(
                                'action' => 'rabbit',
                                'params' => array(
                                    'amqp' => array ( 
                                        "host"      => "host",
                                        "vhost"     => "vhost",
                                        "login"     => "bus",
                                        "password"  => "bus",
                                        "exchange"  => "bus-exchange-devel",
                                        "queue"     => "bus-queue-devel",
                                        "routingKey"=> "bus.key.devel",
                                    ),
                                    'message' => array(
                                        array(
                                            'version' => '1.0',
                                            'action'  => 'imagick_tasks', // имя workflow для Bus
                                            'project' => 'project_name',
                                            'task'    => array(
                                                'action'          => 'crop',
                                                'sourceFile'      => 'imagick_test1_crop_scale_annotateMosaic.jpg',
                                                'destinationFile' => 'imagick_test1_crop_scale_annotateMosaic_crop.jpg',
                                                'params' => array(
                                                    'width'   => 300,
                                                    'height'  => 400,
                                                    'x'       => 50,
                                                    'y'       => 50,
                                                ),
                                                'quality' => 80,
                                            ),
                                        ),
                                    )
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'action' => 'resize',
                'destinationFile' => 'imagick_test1_crop_resizeFit.jpg',
                'params' => array(
                    'x'   => 800,
                    'y'   => 800,
                    'bestfit' => true,
                ),
            ),
            array(
                'action' => 'resize',
                'destinationFile' => 'imagick_test1_crop_resizeSinc.jpg',
                'params' => array(
                    'x'   => 800,
                    'y'   => 800,
                    'filter' => 'Imagick::FILTER_SINC',
                ),
            ),
            array(
                'action' => 'resize',
                'destinationFile' => 'imagick_test1_crop_resizeBlur8.jpg',
                'params' => array(
                    'x'   => 800,
                    'y'   => 800,
                    'blur' => 8,
                ),
            ),
            array(
                'action' => 'resize',
                'destinationFile' => 'imagick_test1_crop_resizeBlur08.jpg',
                'params' => array(
                    'x'   => 800,
                    'y'   => 800,
                    'blur' => 0.8,
                    'quality' => 80,
                ),
            ),
        ),
    );
    $messageService = array(
        array(
            'version' => '1.0',
            'plugin'  => 'imagick_tasks', // имя сервиса
            'action'  => 'peform',        // имя метода сервиса
            'project' => 'project_name',
            'task'   => $task,
        ),
    );

    echo json_encode($messageService, JSON_PRETTY_PRINT);
}
