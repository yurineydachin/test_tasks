# Responsible for parsing input
require 'reservation_request'

describe ReservationRequest do
  describe '.parse' do
   it 'should return an error for non-numeric input' do
     expect { described_class.parse('abv') }.to raise_exception(StandardError, 'Invalid input: abv')
     expect { described_class.parse('10abv') }.to raise_exception(StandardError, 'Invalid input: 10abv')
     expect { described_class.parse('abv10') }.to raise_exception(StandardError, 'Invalid input: abv10')
   end

   it 'should return an error for multiple numbers in input' do
     expect { described_class.parse('10 12') }.to raise_exception(StandardError, 'Invalid input: 10 12')
   end

   it 'should return a valid ReservationRequest for valid input' do
     expect(described_class.parse('10').number_of_guests).to eq(10)
   end
  end
end
