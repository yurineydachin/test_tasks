# Responsible for determining the reservation itself
require 'reservation'
require 'room'

describe Reservation do
  describe '#to_s' do
    subject { reservation.to_s }

    let(:single_room) { Room.new('Single', 1, 20) }
    let(:double_room) { Room.new('Double', 2, 40) }

    context 'one room' do
      let!(:reservation) { described_class.new([single_room]) }

      it { is_expected.to eq('Single - $20') }
    end

    context 'two rooms' do
      let!(:reservation) { described_class.new([single_room, double_room]) }

      it { is_expected.to eq('Single Double - $60') }
    end
  end
end
