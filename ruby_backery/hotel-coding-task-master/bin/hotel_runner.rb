require 'optparse'
require_relative '../lib/reservation_request'
require_relative '../lib/reservation_finder'
require_relative '../lib/reservation'
require_relative '../lib/room'
require_relative '../lib/hotel'

# Process commandline/args

# Initialise inventory

# Create Hotel

# Send requests to Hotel
def setup_hotel
  single = Room.new('Single', 1, 30)
  double = Room.new('Double', 2, 50)
  family = Room.new('Family', 4, 85)

  hotel = Hotel.new
  hotel.add_room_type(single, 2)
  hotel.add_room_type(double, 3)
  hotel.add_room_type(family, 1)
  hotel
end

hotel = setup_hotel

ARGF.each do |line|
  begin
    p hotel.make_reservation(ReservationRequest.parse(line))
  rescue StandardError
    p 'No option'
  end
end
