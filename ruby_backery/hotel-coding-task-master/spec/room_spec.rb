# Responsible for representing a room oocupancy and price
require 'room'

describe Room do
  let(:room) { described_class.new('Single', 1, 20) }

  describe '.type' do
    subject { room.type }

    it { is_expected.to eq('Single') }
  end

  describe '.sleeps' do
    subject { room.sleeps }

    it { is_expected.to eq(1) }
  end

  describe '.price' do
    subject { room.price }

    it { is_expected.to eq(20) }
  end
end
