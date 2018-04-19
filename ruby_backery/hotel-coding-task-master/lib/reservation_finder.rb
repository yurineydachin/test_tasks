class ReservationFinder
  def initialize(starting_inventory)
    @inventory = starting_inventory
  end

  def solve(num_guests)
    @best_reservation = nil
    find_reservations(@inventory, [], 0, num_guests)
    @best_reservation
  end

  private

  def find_reservations(inventory, current_rooms, current_guests, target_guests)
    return if inventory.empty?
    room_type, room_details = inventory.first
    quantity = room_details[:quantity]
    while (quantity >= 0) do
      if (current_guests + (quantity * room_details[:room].sleeps) <= target_guests) then
        new_rooms = current_rooms + Array.new(quantity, room_details[:room])
        check_new_rooms(inventory.reject {|i| i == room_type}, new_rooms, target_guests)
      end
      quantity -= 1
    end
  end

  def check_new_rooms(new_inventory, new_rooms, target_guests)
    reservation = Reservation.new(new_rooms)
    if (reservation.total_sleeps == target_guests) then
      score_new_reservation(reservation)
    else
      next_room_type( new_inventory, new_rooms, reservation.total_sleeps, target_guests)
    end
  end


  def score_new_reservation(new_reservation)
    if (@best_reservation.nil? || @best_reservation.total_cost > new_reservation.total_cost) then
      @best_reservation = new_reservation
    end
  end

  def next_room_type(inventory, current_rooms, current_guests, target_guests)
    find_reservations(inventory, current_rooms, current_guests, target_guests) if !inventory.empty?
  end
end
