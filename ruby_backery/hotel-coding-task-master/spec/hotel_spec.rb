require 'reservation_request'
require 'reservation'
require 'reservation_finder'
require 'room'
require 'hotel'

describe Hotel do
  subject(:hotel) { described_class.new }
  let(:single_room) { Room.new('Single', 1, 20) }
  let(:double_room) { Room.new('Double', 2, 30) }
  let(:deluxe_room) { Room.new('Deluxe', 1, 30) }

  describe '#add_room_type' do
    it 'raises an exception if the type is invalid' do
      expect { hotel.add_room_type([1, 2, 3], 4) }
        .to raise_exception(Hotel::InvalidRoom)
    end

    it 'does not allow room types with duplicate names' do
      hotel.add_room_type(single_room, 2)

      expect { hotel.add_room_type(single_room, 5) }
        .to raise_exception(Hotel::DuplicateRoomType)
    end

    it 'allows room types with different names' do
      hotel.add_room_type(single_room, 2)
      expect { hotel.add_room_type(double_room, 5) }.to_not raise_exception
    end
  end

  describe '#make_reservation' do
    let(:cheap_room) { Room.new('Cheaper', 2, 20) }
    let(:right_size_room) { Room.new('RightSize', 1, 30) }
    let(:premier_room) { Room.new('Premier', 1, 40) }
    let(:boutique_room) { Room.new('Boutique', 1, 50) }
    let(:twin_room) { Room.new('Twin', 2, 50) }
    let(:family_room) { Room.new('Double', 4, 85) }

    it 'returns "No option" when there are no rooms' do
      expect(hotel.make_reservation(ReservationRequest.new(6)))
        .to eq('No option')
    end

    it 'returns "No option" when number of guests exceeds total capacity' do
      hotel.add_room_type(single_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(6)))
        .to eq('No option')
    end

    it 'returns the one room that has the right number of guests' do
      hotel.add_room_type(single_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(1)))
        .to eq(Reservation.new([single_room]).to_s)
    end

    it 'returns the cheapest where two rooms match with the same no. of sleeps' do
      hotel.add_room_type(deluxe_room, 1)
      hotel.add_room_type(single_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(1)))
        .to eq(Reservation.new([single_room]).to_s)
    end

    it 'returns the only a matching number of sleeps where a cheaper option with more sleeps exists' do
      hotel.add_room_type(cheap_room, 1)
      hotel.add_room_type(right_size_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(1)))
        .to eq(Reservation.new([right_size_room]).to_s)
    end

    it 'returns cheapest when multiple rooms match the same no. of sleeps' do
      hotel.add_room_type(single_room, 1)
      hotel.add_room_type(deluxe_room, 1)
      hotel.add_room_type(premier_room, 1)
      hotel.add_room_type(boutique_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(1)))
        .to eq(Reservation.new([single_room]).to_s)
    end

    it 'processes multiple rooms correctly' do
      hotel.add_room_type(single_room, 2)
      hotel.add_room_type(twin_room, 3)
      hotel.add_room_type(family_room, 1)

      expect(hotel.make_reservation(ReservationRequest.new(4)))
        .to eq(Reservation.new([family_room]).to_s)
    end
  end
end
