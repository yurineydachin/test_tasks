class Hotel
  InvalidRoom = Class.new(StandardError)
  DuplicateRoomType = Class.new(StandardError)

  attr_accessor :inventory

  def initialize
    @inventory = {}
  end

  def add_room_type(room, quantity)
    raise(InvalidRoom, room) unless room.instance_of?(Room)
    raise(DuplicateRoomType, "Hotel already has room type: #{room.type}") if
      inventory.key?(room.type)

    inventory[room.type] = {
      room: room,
      quantity: quantity
    }
  end

  def make_reservation(request)
    best_reservation = ReservationFinder.new(inventory).solve(
      request.number_of_guests
    )

    if best_reservation
      best_reservation.to_s
    else
      'No option'
    end
  end
end
