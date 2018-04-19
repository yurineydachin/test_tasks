class Room
  attr_reader :type, :sleeps, :price

  def initialize(type, sleeps, price)
    @type = type
    @sleeps = sleeps
    @price = price
  end
end
