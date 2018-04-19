require 'reservation_request'
require 'reservation'
require 'reservation_finder'
require 'room'
require 'hotel'

describe 'ReservationFinder' do
  describe '#solve' do
    it 'should return "No option" when there are no rooms' do
      expect(ReservationFinder.new([]).solve(6)).to eq(nil)
    end

    it 'should return "No option" when requested number of guests exceeds total capacity' do
      room1 = Room.new("Single", 1, 20)
      finder = ReservationFinder.new({room1.type => {:room => room1, :quantity => 1}})
      expect(finder.solve(6)).to eq(nil)
    end

    it 'should return the one room that has the right number of guests' do
      room1 = Room.new("Single", 1, 20)
      finder = ReservationFinder.new({room1.type => {:room => room1, :quantity => 1}})
      expect(finder.solve(1)).to eq(Reservation.new([room1]))
    end

    it 'should return the cheapest where two rooms match with the same number of sleeps' do
      room1 = Room.new("Single", 1, 20)
      room2 = Room.new("Deluxe", 1, 30)
      finder = ReservationFinder.new({
        room1.type => {:room => room1, :quantity => 1},
        room2.type => {:room => room2, :quantity => 1}})
      expect(finder.solve(1)).to eq(Reservation.new([room1]))
    end

    it 'should return the only a matching number of sleeps where a cheaper option with more sleeps exists' do
      room1 = Room.new("Cheaper", 2, 20)
      room2 = Room.new("RightSize", 1, 30)
      finder = ReservationFinder.new({
        room1.type => {:room => room1, :quantity => 1},
        room2.type => {:room => room2, :quantity => 1}})
      expect(finder.solve(1)).to eq(Reservation.new([room2]))
    end

    it 'should return the cheapest where multiple rooms match with the same number of sleeps' do
      room1 = Room.new("Single", 1, 20)
      room2 = Room.new("Deluxe", 1, 30)
      room3 = Room.new("Premier", 1, 40)
      room4 = Room.new("Boutique", 1, 50)
      finder = ReservationFinder.new({
        room1.type => {:room => room1, :quantity => 1},
        room3.type => {:room => room3, :quantity => 1},
        room4.type => {:room => room4, :quantity => 1},
        room2.type => {:room => room2, :quantity => 1}})
      expect(finder.solve(1)).to eq(Reservation.new([room1]))
    end

    it "should process multiple rooms correctly" do
      room1 = Room.new("Single", 1, 30)
      room2 = Room.new("Double", 2, 50)
      room3 = Room.new("Family", 4, 85)
      finder = ReservationFinder.new({
        room1.type => {:room => room1, :quantity => 2},
        room3.type => {:room => room3, :quantity => 1},
        room2.type => {:room => room2, :quantity => 3}})

      expect(finder.solve(4)).to eq(Reservation.new([room3]))
    end
  end
end
