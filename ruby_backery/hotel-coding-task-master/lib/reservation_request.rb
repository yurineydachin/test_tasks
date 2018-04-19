class ReservationRequest
  attr_reader :number_of_guests

  def initialize(number_of_guests)
    @number_of_guests = number_of_guests
  end

  def self.parse(input)
    ReservationRequest.new(Integer(input))
  rescue StandardError
    raise StandardError, "Invalid input: #{input}"
  end
end
