#!/usr/bin/env ruby

class Pack
    def initialize(capacity, price)
        @capacity = capacity
        @price = price
    end

    def getCapacity
        return @capacity
    end

    def getPrice
        return @price
    end
end

class Product
    def initialize(code, name, packs)
        @code = code
        @name = name
        if !packs.kind_of?(Array)
            puts "Need array of Pack #{packs}"
            return
        end

        @packs = {}
        packs.each do |pack|
            if pack.is_a? Pack
                @packs[pack.getCapacity()] = pack
            else
                puts "Need Pack, but given #{pack}"
            end
        end
    end

    def getCode
        return @code
    end

    def getName
        return @name
    end

    def getPacks
        return @packs
    end

    def getPacksCount
        return @packs.keys.sort_by{|v| -v}
    end
end

class BakeryComposer

    def initialize(products)
        if !products.kind_of?(Array)
            puts "Need array of Product #{products}"
            return
        end

        @products = {}
        products.each do |product|
            if product.is_a? Product
                @products[product.getCode()] = product
            else
                puts "Need Product, but given #{product}"
            end
        end
    end

    def calculate(order)
        res = {}
        if !order.kind_of?(Hash)
            puts "Need hash of code: count #{order}"
            return res
        end

        puts "Products #{@products}"

        order.each do |code, count|
            p = @products[code]
            if p.nil?
                puts "Product with code #{code} not found"
            else
                res[p] = findPacks(count, p)
            end
        end
        return res
    end

    private
    def findPacks(count, product)
        hash = findPacksAndCount(count, product.getPacksCount())
        packs = product.getPacks()
        res = {}
        hash.each do |pack, count|
            if packs[pack].nil?
                puts "Wrong package #{pack}, for product #{product.getCode()}"
            else
                res[packs[pack]] = count
            end
        end
        return res
    end
end

def findPacksAndCount(basis, packs)
    if !packs.kind_of?(Array)
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

    puts bakery.calculate({"VS5": 10, "MB11": 14, "CF": 13})
end
