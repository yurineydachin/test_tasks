#!/usr/bin/env ruby

class Pack
    def initialize(capacity, price)
        raise unless capacity.is_a? Integer
        raise unless price.is_a? Numeric
        @capacity = capacity
        @price = price
    end

    def capacity
        @capacity
    end

    def price
        @price
    end
end

class Product
    def initialize(code, name, packs)
        raise unless code.is_a? String
        raise unless name.is_a? String
        raise unless packs.is_a? Array
        raise unless packs.length > 0
        @code = code
        @name = name

        @packs = {}
        packs.each do |pack|
            if pack.is_a? Pack
                @packs[pack.capacity] = pack
            else
                puts "Need Pack, but given #{pack}"
            end
        end
    end

    def code
        @code
    end

    def name
        @name
    end

    def packs
        @packs
    end

    def packsCapacity
        @packs.keys.sort_by{|v| -v}
    end
end

class Order
    def initialize()
        @items = []
    end

    def addItem(product, count)
        raise unless product.is_a? Product
        raise unless count.is_a? Integer

        @items << OrderItem.new(product, count)
    end

    def items
        @items
    end

    def to_s
        res = ""
        @items.each do |orderItem|
            res += orderItem.to_s
        end
        res
    end
end

class OrderItem
    def initialize(product, count)
        raise unless product.is_a? Product
        raise unless count.is_a? Integer
        @product = product
        @count = count
        @packHash = {}

        packHash = findPacksAndCount(count, @product.packsCapacity)
        return if !packHash.is_a? Hash

        packs = @product.packs
        packHash.each do |packCapacity, count|
            if packs[packCapacity].nil?
                puts "Wrong packCapacity #{packCapacity}, for product #{product.code}"
            else
                @packHash[packs[packCapacity]] = count
            end
        end
    end

    def count
        @count
    end

    def packHash
        @packHash
    end

    def totalPrice
        res = 0.0
        @packHash.each do |pack, count|
            res += pack.price * count
        end
        res
    end

    def to_s
        res = sprintf("%d %s $%0.2f\n", @count, @product.code, self.totalPrice)
        @packHash.each do |pack, count|
            res += sprintf("    %d x %d $%0.2f\n", count, pack.capacity, pack.price)
        end
        res
    end
end

class BakeryComposer
    def initialize(products)
        raise unless products.is_a? Array

        @products = {}
        products.each do |product|
            if product.is_a? Product
                @products[product.code] = product
            else
                puts "Need Product, but given #{product}"
            end
        end
    end

    def calculateOrder(basket)
        raise unless basket.is_a? Hash

        order = Order.new
        basket.each do |code, count|
            p = @products[code]
            if p.nil?
                puts "Product with code #{code} not found"
            else
                order.addItem(p, count)
            end
        end
        order
    end
end

def findPacksAndCount(basis, packs)
    raise unless packs.is_a? Array

    first = packs.shift
    return {first => basis / first} if basis == 0 || basis % first == 0
    return nil if packs.length == 0

    (basis / first).step(0, -1) do |count|
        child = findPacksAndCount(basis - count * first, packs.clone)
        if !child.nil?
            if count > 0
                return {first => count}.merge(child)
            else
                return child
            end
        end
    end
    return nil
end

if __FILE__ == $0
    bakery = BakeryComposer.new([
        Product.new("VS5", "Vegemite Scroll", [
            Pack.new(3, 6.99),
            Pack.new(5, 8.99),
        ]),
        Product.new("MB11", "Blueberry Muffin", [
            Pack.new(2, 9.95),
            Pack.new(5, 16.95),
            Pack.new(8, 24.95),
        ]),
        Product.new("CF", "Croissant", [
            Pack.new(3, 5.95),
            Pack.new(5, 9.95),
            Pack.new(9, 16.99),
        ]),
    ])

    puts bakery.calculateOrder({"VS5" => 10, "MB11" => 14, "CF" => 13})
end
