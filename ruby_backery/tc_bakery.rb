require_relative "bakery"
require "test/unit"

class TestBakery < Test::Unit::TestCase

    def test_findPacksAndCount
        assert_equal({1 => 10}, findPacksAndCount(10, [1]))
        assert_equal({2 => 5}, findPacksAndCount(10, [2]))
        assert_nil(findPacksAndCount(10, [3]))
        assert_nil(findPacksAndCount(10, [4]))
        assert_equal({5 => 2}, findPacksAndCount(10, [5]))

        assert_equal({3 => 3, 1 => 1}, findPacksAndCount(10, [3,1]))
        assert_equal({3 => 2, 2 => 2}, findPacksAndCount(10, [3,2]))
        assert_equal({4 => 2, 2 => 1}, findPacksAndCount(10, [4,2]))
        assert_equal({6 => 1, 2 => 2}, findPacksAndCount(10, [6,2]))
        assert_equal({2 => 5}, findPacksAndCount(10, [7,2]))
        assert_equal({8 => 1, 2 => 1}, findPacksAndCount(10, [8,2]))
        assert_equal({2 => 5}, findPacksAndCount(10, [9,2]))

        assert_equal({9 => 1, 1 => 1}, findPacksAndCount(10, [9,1]))

        assert_equal({15 => 6, 9 => 1, 1 => 1}, findPacksAndCount(100, [15, 9, 1]))
    end

    def test_createPack_wrongParams
        assert_raise( RuntimeError ) { Pack.new("123", "123") }
        assert_raise( RuntimeError ) { Pack.new(5, "123") }
    end

    def test_createProduct_wrongParams
        assert_raise( RuntimeError ) { Product.new(1, 2, 3) }
        assert_raise( RuntimeError ) { Product.new("VS5", 2, 3) }
        assert_raise( RuntimeError ) { Product.new("VS5", "Vegemite Scroll", 3) }
        assert_raise( RuntimeError ) { Product.new("VS5", "Vegemite Scroll", []) }
    end

    def test_createProduct
        product = Product.new("VS5", "Vegemite Scroll", [
            Pack.new(3, 6.99),
            Pack.new(5, 8.99),
        ])

        assert_equal("VS5", product.code)
        assert_equal("Vegemite Scroll", product.name)
        assert_equal(2, product.packs.length)
        assert_equal([5, 3], product.packsCapacity)
    end

    def test_createOrderItem_wrongParams
        assert_raise( RuntimeError ) { OrderItem.new(1, "1234") }
        assert_raise( RuntimeError ) { OrderItem.new(Product.new("VS5", "Vegemite Scroll", [Pack.new(3, 6.99)]), "1234") }
    end

    def test_createOrderItem
        p1 = Pack.new(3, 6.99)
        p2 = Pack.new(5, 8.99)
        orderItem = OrderItem.new(Product.new("VS5", "Vegemite Scroll", [p1, p2]), 10)

        assert_equal(10, orderItem.count)
        assert_equal({p2 => 2}, orderItem.packHash)
        assert_equal(17.98, orderItem.totalPrice)
        assert_equal("10 VS5 $17.98\n    2 x 5 $8.99\n", orderItem.to_s)
    end

    def test_createOrderItem_wrongCount
        p1 = Pack.new(3, 6.99)
        p2 = Pack.new(5, 8.99)
        orderItem = OrderItem.new(Product.new("VS5", "Vegemite Scroll", [p1, p2]), 7)

        assert_equal(7, orderItem.count)
        assert_equal({}, orderItem.packHash)
        assert_equal(0, orderItem.totalPrice)
        assert_equal("7 VS5 $0.00\n", orderItem.to_s)
    end

    def test_createOrder
        p1 = Pack.new(3, 6.99)
        p2 = Pack.new(5, 8.99)
        order = Order.new
        order.addItem(Product.new("VS5", "Vegemite Scroll", [p1, p2]), 10)

        assert_equal(1, order.items.length)
        assert_equal(10, order.items[0].count)
        assert_equal({p2 => 2}, order.items[0].packHash)
        assert_equal(17.98, order.items[0].totalPrice)
        assert_equal("10 VS5 $17.98\n    2 x 5 $8.99\n", order.to_s)
    end

    def test_createOrder_2Products
        p1 = Pack.new(3, 6.99)
        p2 = Pack.new(5, 8.99)
        order = Order.new
        order.addItem(Product.new("VS5", "Vegemite Scroll", [p1, p2]), 10)
        order.addItem(Product.new("MB11", "Blueberry Muffin", [p1, p2]), 14)

        assert_equal(2, order.items.length)

        assert_equal(10, order.items[0].count)
        assert_equal({p2 => 2}, order.items[0].packHash)
        assert_equal(17.98, order.items[0].totalPrice)
        assert_equal("10 VS5 $17.98\n    2 x 5 $8.99\n", order.items[0].to_s)

        assert_equal(14, order.items[1].count)
        assert_equal({p2 => 1, p1 => 3}, order.items[1].packHash)
        assert_equal(29.96, order.items[1].totalPrice)
        assert_equal("14 MB11 $29.96\n    1 x 5 $8.99\n    3 x 3 $6.99\n", order.items[1].to_s)

        assert_equal("10 VS5 $17.98\n    2 x 5 $8.99\n14 MB11 $29.96\n    1 x 5 $8.99\n    3 x 3 $6.99\n", order.to_s)
    end

    def test_createBakery
        p1 = Pack.new(3, 6.99)
        p2 = Pack.new(5, 8.99)
        order = BakeryComposer.new([Product.new("VS5", "Vegemite Scroll", [p1, p2])]).calculateOrder({"VS5" => 10})

        assert_equal(1, order.items.length)
        assert_equal(10, order.items[0].count)
        assert_equal({p2 => 2}, order.items[0].packHash)
        assert_equal(17.98, order.items[0].totalPrice)
        assert_equal("10 VS5 $17.98\n    2 x 5 $8.99\n", order.to_s)
    end
end
