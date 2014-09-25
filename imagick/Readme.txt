Задача:
Нужен сервис обработки картинок, чтобы все наши проекты могли его использовать, а не делали все по своему. Сервис принимает комманду/набор комманд, выполняет их и возвращает отчет о выполнении. Результируюищие файлы можно забрать различными способами - подмонтировать директорию, отправить файлзапросом или запросить его по http.

Функции:
1. Кроп
2. Ресайз / масштабирование
3. Поворот
4. Отправка сообщения в очередь AMQP, на почту
5. Наложить ватермарк, написать текст
6. Наложить текущее изображение на холст
7. Применить фильтры: растворение, ...

Пример составной комманды:
{
    "version": "1.0",
    "plugin": "imagick_tasks",
    "action": "peform",
    "project": "project_name",
    "task": {
        "action": "crop",
        "sourceFile": "imagick_test1.jpg",
        "destinationFile": "imagick_test1_crop.jpg",
        "params": {
            "width": 1300,
            "height": 1400,
            "x": 1500,
            "y": 1600
        },
        "subTasks": [
            {
                "action": "scale",
                "destinationFile": "imagick_test1_crop_scale.jpg",
                "params": {
                    "scale": 600
                },
                "subTasks": [
                    {
                        "action": "extent",
                        "destinationFile": "imagick_test1_crop_scale_extent.jpg",
                        "params": {
                            "width": 1000,
                            "height": 700,
                            "x": "center",
                            "y": "center",
                            "background": "blue"
                        },
                        "subTasks": [
                            {
                                "action": "rotate",
                                "destinationFile": "imagick_test1_crop_scale_extent_rotate.jpg",
                                "params": {
                                    "degrees": 90
                                },
                                "subTasks": [
                                    {
                                        "action": "filter",
                                        "destinationFile": "imagick_test1_crop_scale_extent_rotate_filter.jpg",
                                        "params": {
                                            "radius": 2
                                        }
                                    },
                                    {
                                        "action": "blur",
                                        "destinationFile": "imagick_test1_crop_scale_extent_rotate_blur.jpg",
                                        "params": {
                                            "radius": 2,
                                            "sigma": 20
                                        }
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        "action": "watermark",
                        "params": {
                            "type": "position",
                            "x": "right",
                            "y": "bottom",
                            "watermark": "ria-watermark.png"
                        },
                        "subTasks": [
                            {
                                "action": "save",
                                "destinationFile": "imagick_test1_crop_scale_watermark.jpg"
                            }
                        ]
                    },
                    {
                        "action": "watermark",
                        "destinationFile": "imagick_test1_crop_scale_watermark2.jpg",
                        "params": {
                            "type": "position",
                            "x": -20,
                            "y": -20,
                            "watermark": "ria-watermark.png"
                        }
                    },
                    {
                        "action": "annotate",
                        "destinationFile": "imagick_test1_crop_scale_annotate.jpg",
                        "params": {
                            "x": "right",
                            "y": -50,
                            "text": "\u041c\u0435\u0434\u0432\u0435\u0434\u0435\u0432 \u0438 \u041a\u043e",
                            "fontSize": 20,
                            "fontColor": "blue",
                            "degrees": 45
                        }
                    },
                    {
                        "action": "annotate",
                        "destinationFile": "imagick_test1_crop_scale_annotateMosaic.jpg",
                        "params": {
                            "type": "mosaic",
                            "text": "   \u041c\u0435\u0434\u0432\u0435\u0434\u0435\u0432 \u0438 \u041a\u043e   ",
                            "fontSize": 20,
                            "fontColor": "#00FF00",
                            "degrees": -45,
                            "composite": "Imagick::COMPOSITE_MODULATE"
                        },
                        "subTasks": [
                            {
                                "action": "mail",
                                "params": {
                                    "to": "yurineudachin@mail.ru",
                                    "subject": "Imagick test result for Yuri",
                                    "filename": "imagick_test1_crop_scale_annotateMosaic.jpg"
                                }
                            },
                            {
                                "action": "rabbit",
                                "params": {
                                    "amqp": {
                                        "host": "host",
                                        "vhost": "vhost",
                                        "login": "bus",
                                        "password": "bus",
                                        "exchange": "bus-exchange-devel",
                                        "queue": "bus-queue-devel",
                                        "routingKey": "bus.key.devel"
                                    },
                                    "message": [
                                        {
                                            "version": "1.0",
                                            "action": "imagick_tasks",
                                            "project": "project_name",
                                            "task": {
                                                "action": "crop",
                                                "sourceFile": "imagick_test1_crop_scale_annotateMosaic.jpg",
                                                "destinationFile": "imagick_test1_crop_scale_annotateMosaic_crop.jpg",
                                                "params": {
                                                    "width": 300,
                                                    "height": 400,
                                                    "x": 50,
                                                    "y": 50
                                                },
                                                "quality": 80
                                            }
                                        }
                                    ]
                                }
                            }
                        ]
                    }
                ]
            },
            {
                "action": "resize",
                "destinationFile": "imagick_test1_crop_resizeFit.jpg",
                "params": {
                    "x": 800,
                    "y": 800,
                    "bestfit": true
                }
            },
            {
                "action": "resize",
                "destinationFile": "imagick_test1_crop_resizeSinc.jpg",
                "params": {
                    "x": 800,
                    "y": 800,
                    "filter": "Imagick::FILTER_SINC"
                }
            },
            {
                "action": "resize",
                "destinationFile": "imagick_test1_crop_resizeBlur8.jpg",
                "params": {
                    "x": 800,
                    "y": 800,
                    "blur": 8
                }
            },
            {
                "action": "resize",
                "destinationFile": "imagick_test1_crop_resizeBlur08.jpg",
                "params": {
                    "x": 800,
                    "y": 800,
                    "blur": 0.8,
                    "quality": 80
                }
            }
        ]
    }
}
