class Reservation
  attr_reader :rooms, :total_cost, :total_sleeps

  def initialize(rooms)
    @rooms = rooms || []
    @total_cost = @rooms.inject(0) { |sum, room| sum + room.price }
    @total_sleeps = @rooms.inject(0) { |sum, room| sum + room.sleeps }
  end

  def to_s
    room_names = @rooms.map(&:type)
    room_names.join(' ') + ' - $' + @total_cost.to_s
  end

  def ==(other)
    to_s == other.to_s
  end
end
