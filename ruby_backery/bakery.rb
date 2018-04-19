#!/usr/bin/env ruby

class Pack
    def initialize(capacity, price)
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
        @code = code
        @name = name
        if !packs.is_a? Array
            puts "Need array of Pack #{packs}"
            return
        end

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
        if !product.is_a? Product
            puts "Need product for Order.addItem, but given: #{product}"
            return
        end
        if !count.is_a? Integer
            puts "Need count as integer for Order.addItem, but given: #{count}"
            return
        end

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
        if !product.is_a? Product
            puts "Need product for OrderItem, but given: #{product}"
            return
        end
        if !count.is_a? Integer
            puts "Need count as integer for OrderItem, but given: #{count}"
            return
        end
        @product = product
        @count = count
        @packHash = {}

        packHash = findPacksAndCount(count, @product.packsCapacity)
        if !packHash.is_a? Hash
            puts "Need hash of packs {packCapacity => count} for OrderItem, but given: #{packHash}"
            return
        end

        packs = @product.packs
        packHash.each do |packCapacity, count|
            if packs[packCapacity].nil?
                puts "Wrong packCapacity #{packCapacity}, for product #{product.code}"
            else
                @packHash[packs[packCapacity]] = count
            end
        end
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
        if !products.is_a? Array
            puts "Need array of Product #{products}"
            return
        end

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
        order = Order.new
        if !basket.is_a? Hash
            puts "Need hash of {code => count, ...} #{basket}"
            return order
        end

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
    if !packs.is_a? Array
        return nil
    end

    first = packs.shift
    if basis == 0 || basis % first == 0
        return {first => basis / first}
    end
    
    if packs.length == 0
        return nil
    end

    (basis / first).step(0, -1) do |n|
        child = findPacksAndCount(basis - n * first, packs.clone)
        if !child.nil?
            if n > 0
                return {first => n}.merge(child)
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
